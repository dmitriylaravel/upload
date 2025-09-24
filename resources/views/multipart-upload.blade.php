<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>S3/R2 Multipart Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .upload-container { border: 2px solid #28a745; padding: 30px; margin: 20px 0; border-radius: 8px; }
        .upload-area { border: 2px dashed #ccc; padding: 40px; text-align: center; margin: 20px 0; border-radius: 8px; }
        .upload-area.dragover { border-color: #28a745; background: #f8fff8; }
        button { padding: 12px 24px; margin: 10px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        button:disabled { opacity: 0.6; cursor: not-allowed; }
        .progress-container { margin: 20px 0; }
        .progress-bar { width: 100%; height: 24px; background: #f0f0f0; border-radius: 12px; overflow: hidden; }
        .progress-fill { height: 100%; background: #28a745; transition: width 0.3s; border-radius: 12px; }
        .progress-text { text-align: center; margin: 10px 0; font-weight: bold; }
        .status { padding: 15px; margin: 15px 0; border-radius: 6px; }
        .status.info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .status.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .status.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .logs { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-top: 20px; max-height: 400px; overflow-y: auto; }
        .log-entry { margin: 5px 0; font-family: monospace; font-size: 12px; }
        .chunk-status { display: grid; grid-template-columns: repeat(auto-fill, 20px); gap: 2px; margin: 10px 0; }
        .chunk { width: 20px; height: 20px; background: #ddd; border-radius: 2px; }
        .chunk.uploading { background: #ffc107; }
        .chunk.completed { background: #28a745; }
        .chunk.failed { background: #dc3545; }
        .file-info { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 6px; }
    </style>
</head>
<body>
    <h1>🚀 S3/R2 Multipart Upload Test</h1>

    <p><strong>This uses native S3/R2 multipart uploads to handle very large files efficiently by uploading them in 5MB chunks.</strong></p>

    <div class="upload-container">
        <h3>📁 Select Large File for Multipart Upload</h3>

        <div class="upload-area" id="uploadArea">
            <div>
                <p><strong>Drop your file here or click to select</strong></p>
                <p>Supports files up to several GB in size</p>
                <input type="file" id="fileInput" style="display: none;">
                <button class="btn-primary" onclick="document.getElementById('fileInput').click()">Choose File</button>
            </div>
        </div>

        <div id="fileInfo" class="file-info" style="display: none;">
            <h4>📊 File Information</h4>
            <div id="fileDetails"></div>
        </div>

        <div id="uploadControls" style="display: none;">
            <button id="startUpload" class="btn-success">🚀 Start Multipart Upload</button>
            <button id="pauseUpload" class="btn-secondary" disabled>⏸️ Pause</button>
            <button id="resumeUpload" class="btn-primary" disabled>▶️ Resume</button>
            <button id="cancelUpload" class="btn-danger" disabled>❌ Cancel</button>
        </div>

        <div id="progressContainer" class="progress-container" style="display: none;">
            <div class="progress-text" id="progressText">Ready to upload...</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
            <div id="uploadStats"></div>
        </div>

        <div id="chunkStatus" class="chunk-status" style="display: none;">
            <!-- Chunk status indicators will be populated here -->
        </div>

        <div id="statusMessage"></div>
    </div>

    <div class="logs" id="logs" style="display: none;">
        <h4>📋 Upload Logs:</h4>
        <div id="logContent"></div>
        <button onclick="clearLogs()">Clear Logs</button>
    </div>

    <script>
        let selectedFile = null;
        let uploadState = {
            uploadId: null,
            key: null,
            chunks: [],
            completedChunks: [],
            failedChunks: [],
            isPaused: false,
            chunkSize: 5 * 1024 * 1024, // 5MB
            totalChunks: 0,
            uploadedBytes: 0
        };

        // File selection handling
        const fileInput = document.getElementById('fileInput');
        const uploadArea = document.getElementById('uploadArea');

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                selectFile(files[0]);
            }
        });

        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                selectFile(e.target.files[0]);
            }
        });

        function selectFile(file) {
            selectedFile = file;
            displayFileInfo(file);
            document.getElementById('uploadControls').style.display = 'block';
            document.getElementById('startUpload').disabled = false;
        }

        function displayFileInfo(file) {
            const fileInfo = document.getElementById('fileInfo');
            const fileDetails = document.getElementById('fileDetails');

            const sizeInMB = (file.size / 1024 / 1024).toFixed(2);
            const chunkCount = Math.ceil(file.size / uploadState.chunkSize);

            fileDetails.innerHTML = `
                <strong>Name:</strong> ${file.name}<br>
                <strong>Size:</strong> ${sizeInMB} MB (${file.size.toLocaleString()} bytes)<br>
                <strong>Type:</strong> ${file.type || 'Unknown'}<br>
                <strong>Chunks:</strong> ${chunkCount} × 5MB chunks<br>
                <strong>Last Modified:</strong> ${new Date(file.lastModified).toLocaleString()}
            `;

            fileInfo.style.display = 'block';
            uploadState.totalChunks = chunkCount;
            createChunkStatusIndicators(chunkCount);
        }

        function createChunkStatusIndicators(chunkCount) {
            const chunkStatus = document.getElementById('chunkStatus');
            chunkStatus.innerHTML = '';

            for (let i = 0; i < chunkCount; i++) {
                const chunk = document.createElement('div');
                chunk.className = 'chunk';
                chunk.id = `chunk-${i + 1}`;
                chunk.title = `Chunk ${i + 1}`;
                chunkStatus.appendChild(chunk);
            }

            chunkStatus.style.display = 'block';
        }

        function updateChunkStatus(chunkNumber, status) {
            const chunk = document.getElementById(`chunk-${chunkNumber}`);
            if (chunk) {
                chunk.className = `chunk ${status}`;
            }
        }

        // Upload functions
        async function startUpload() {
            if (!selectedFile) return;

            addLog('🚀 Initiating multipart upload...');
            setUploadControls('uploading');
            showStatus('Initiating upload...', 'info');

            try {
                // Initiate multipart upload
                const response = await fetch('/multipart/initiate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        filename: selectedFile.name,
                        filesize: selectedFile.size,
                        filetype: selectedFile.type
                    })
                });

                const result = await response.json();

                if (result.success) {
                    uploadState.uploadId = result.upload_id;
                    uploadState.key = result.key;
                    uploadState.chunkSize = result.chunk_size;
                    uploadState.totalChunks = result.total_chunks;

                    addLog(`✅ Upload initiated: ${result.upload_id}`);
                    addLog(`📂 Key: ${result.key}`);
                    addLog(`📦 Will upload ${result.total_chunks} chunks of ${(result.chunk_size / 1024 / 1024).toFixed(1)}MB each`);

                    // Start uploading chunks
                    await uploadChunks();
                } else {
                    showStatus(`Error: ${result.error}`, 'error');
                    addLog(`❌ Initiation failed: ${result.error}`);
                    setUploadControls('idle');
                }
            } catch (error) {
                showStatus(`Error: ${error.message}`, 'error');
                addLog(`❌ Initiation error: ${error.message}`);
                setUploadControls('idle');
            }
        }

        async function uploadChunks() {
            document.getElementById('progressContainer').style.display = 'block';

            for (let chunkNumber = 1; chunkNumber <= uploadState.totalChunks; chunkNumber++) {
                if (uploadState.isPaused) {
                    addLog('⏸️ Upload paused');
                    return;
                }

                if (uploadState.completedChunks.includes(chunkNumber)) {
                    continue; // Skip already uploaded chunks
                }

                await uploadChunk(chunkNumber);
            }

            // Complete the upload
            await completeUpload();
        }

        async function uploadChunk(chunkNumber) {
            const start = (chunkNumber - 1) * uploadState.chunkSize;
            const end = Math.min(start + uploadState.chunkSize, selectedFile.size);
            const chunk = selectedFile.slice(start, end);

            updateChunkStatus(chunkNumber, 'uploading');
            addLog(`📤 Uploading chunk ${chunkNumber}/${uploadState.totalChunks} (${(chunk.size / 1024 / 1024).toFixed(2)}MB)`);

            try {
                const formData = new FormData();
                formData.append('upload_id', uploadState.uploadId);
                formData.append('key', uploadState.key);
                formData.append('chunk_number', chunkNumber);
                formData.append('chunk', chunk);

                const response = await fetch('/multipart/upload-chunk', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    uploadState.completedChunks.push(chunkNumber);
                    uploadState.uploadedBytes += chunk.size;

                    updateChunkStatus(chunkNumber, 'completed');
                    updateProgress();

                    addLog(`✅ Chunk ${chunkNumber} completed (ETag: ${result.etag.substring(0, 8)}...)`);
                } else {
                    updateChunkStatus(chunkNumber, 'failed');
                    uploadState.failedChunks.push(chunkNumber);
                    addLog(`❌ Chunk ${chunkNumber} failed: ${result.error}`);
                    throw new Error(`Chunk ${chunkNumber} failed: ${result.error}`);
                }
            } catch (error) {
                updateChunkStatus(chunkNumber, 'failed');
                uploadState.failedChunks.push(chunkNumber);
                addLog(`❌ Chunk ${chunkNumber} error: ${error.message}`);
                throw error;
            }
        }

        async function completeUpload() {
            addLog('🔄 Completing multipart upload...');
            showStatus('Completing upload...', 'info');

            try {
                // Prepare parts data
                const parts = uploadState.completedChunks.map(chunkNumber => ({
                    part_number: chunkNumber,
                    etag: `"${chunkNumber}"` // This should be the actual ETag from upload response
                }));

                const response = await fetch('/multipart/complete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        upload_id: uploadState.uploadId,
                        key: uploadState.key,
                        parts: uploadState.completedChunks.map((chunkNumber, index) => ({
                            part_number: chunkNumber,
                            etag: uploadState.chunks[index]?.etag || `"${chunkNumber}"`
                        }))
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showStatus(`✅ Upload completed successfully! File size: ${result.size}`, 'success');
                    addLog(`🎉 Upload completed: ${result.key}`);
                    addLog(`🔗 URL: ${result.url}`);
                    addLog(`📊 Final size: ${result.size}`);
                    setUploadControls('completed');
                } else {
                    showStatus(`Error completing upload: ${result.error}`, 'error');
                    addLog(`❌ Completion failed: ${result.error}`);
                    setUploadControls('idle');
                }
            } catch (error) {
                showStatus(`Error completing upload: ${error.message}`, 'error');
                addLog(`❌ Completion error: ${error.message}`);
                setUploadControls('idle');
            }
        }

        function updateProgress() {
            const percentage = (uploadState.completedChunks.length / uploadState.totalChunks) * 100;
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const uploadStats = document.getElementById('uploadStats');

            progressFill.style.width = percentage + '%';
            progressText.textContent = `Uploading... ${percentage.toFixed(1)}% (${uploadState.completedChunks.length}/${uploadState.totalChunks} chunks)`;

            const uploadedMB = (uploadState.uploadedBytes / 1024 / 1024).toFixed(2);
            const totalMB = (selectedFile.size / 1024 / 1024).toFixed(2);

            uploadStats.innerHTML = `
                <div style="margin-top: 10px; font-size: 12px; color: #666;">
                    Uploaded: ${uploadedMB} MB / ${totalMB} MB |
                    Completed: ${uploadState.completedChunks.length} chunks |
                    Failed: ${uploadState.failedChunks.length} chunks
                </div>
            `;
        }

        function setUploadControls(state) {
            const startBtn = document.getElementById('startUpload');
            const pauseBtn = document.getElementById('pauseUpload');
            const resumeBtn = document.getElementById('resumeUpload');
            const cancelBtn = document.getElementById('cancelUpload');

            switch (state) {
                case 'idle':
                    startBtn.disabled = false;
                    pauseBtn.disabled = true;
                    resumeBtn.disabled = true;
                    cancelBtn.disabled = true;
                    break;
                case 'uploading':
                    startBtn.disabled = true;
                    pauseBtn.disabled = false;
                    resumeBtn.disabled = true;
                    cancelBtn.disabled = false;
                    break;
                case 'paused':
                    startBtn.disabled = true;
                    pauseBtn.disabled = true;
                    resumeBtn.disabled = false;
                    cancelBtn.disabled = false;
                    break;
                case 'completed':
                    startBtn.disabled = false;
                    pauseBtn.disabled = true;
                    resumeBtn.disabled = true;
                    cancelBtn.disabled = true;
                    break;
            }
        }

        function pauseUpload() {
            uploadState.isPaused = true;
            setUploadControls('paused');
            showStatus('Upload paused', 'info');
            addLog('⏸️ Upload paused by user');
        }

        function resumeUpload() {
            uploadState.isPaused = false;
            setUploadControls('uploading');
            showStatus('Resuming upload...', 'info');
            addLog('▶️ Resuming upload...');
            uploadChunks();
        }

        async function cancelUpload() {
            if (uploadState.uploadId) {
                try {
                    await fetch('/multipart/abort', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            upload_id: uploadState.uploadId,
                            key: uploadState.key
                        })
                    });

                    addLog('❌ Upload cancelled and cleaned up');
                } catch (error) {
                    addLog('⚠️ Upload cancelled but cleanup may have failed');
                }
            }

            // Reset state
            uploadState = {
                uploadId: null,
                key: null,
                chunks: [],
                completedChunks: [],
                failedChunks: [],
                isPaused: false,
                chunkSize: 5 * 1024 * 1024,
                totalChunks: 0,
                uploadedBytes: 0
            };

            setUploadControls('idle');
            showStatus('Upload cancelled', 'info');
            document.getElementById('progressContainer').style.display = 'none';

            // Reset chunk indicators
            const chunks = document.querySelectorAll('.chunk');
            chunks.forEach(chunk => {
                chunk.className = 'chunk';
            });
        }

        function showStatus(message, type) {
            const statusElement = document.getElementById('statusMessage');
            statusElement.innerHTML = `<div class="status ${type}">${message}</div>`;
        }

        function addLog(message) {
            const logs = document.getElementById('logs');
            const logContent = document.getElementById('logContent');
            const timestamp = new Date().toLocaleTimeString() + '.' + new Date().getMilliseconds().toString().padStart(3, '0');

            logContent.innerHTML += `<div class="log-entry">[${timestamp}] ${message}</div>`;
            logs.style.display = 'block';
            logContent.scrollTop = logContent.scrollHeight;
        }

        function clearLogs() {
            document.getElementById('logContent').innerHTML = '';
        }

        // Event listeners
        document.getElementById('startUpload').addEventListener('click', startUpload);
        document.getElementById('pauseUpload').addEventListener('click', pauseUpload);
        document.getElementById('resumeUpload').addEventListener('click', resumeUpload);
        document.getElementById('cancelUpload').addEventListener('click', cancelUpload);

        // Initialize
        addLog('🚀 Multipart upload system initialized');
        addLog('📝 Select a file to begin uploading in 5MB chunks');
    </script>
</body>
</html>