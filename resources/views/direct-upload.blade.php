<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Direct Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
        .upload-section { border: 2px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .upload-section h3 { margin-top: 0; }
        .standard { border-color: #007bff; }
        .movefiles { border-color: #28a745; }
        button { padding: 10px 20px; margin: 10px 0; border: none; border-radius: 4px; cursor: pointer; }
        .btn-standard { background: #007bff; color: white; }
        .btn-movefiles { background: #28a745; color: white; }
        .result { margin-top: 20px; padding: 15px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .loading { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .logs { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Direct Upload Test - Bypassing Livewire</h1>

    <p><strong>This test bypasses Livewire entirely and uses direct Laravel file uploads to compare standard vs moveFiles() approaches.</strong></p>

    <div class="upload-section standard">
        <h3>🔄 Standard Upload (Copy Method)</h3>
        <p>Uses Laravel's standard <code>store()</code> method - copies temporary file to final location</p>
        <input type="file" id="standardFile" accept="*/*">
        <br>
        <button class="btn-standard" onclick="uploadStandard()">Upload Standard</button>
        <div id="standardResult"></div>
    </div>

    <div class="upload-section movefiles">
        <h3>🚀 MoveFiles Upload (Move Method)</h3>
        <p>Uses direct file move operation - moves temporary file without copying (simulates Filament's moveFiles())</p>
        <input type="file" id="moveFilesFile" accept="*/*">
        <br>
        <button class="btn-movefiles" onclick="uploadMoveFiles()">Upload MoveFiles</button>
        <div id="moveFilesResult"></div>
    </div>

    <div class="logs" id="logs" style="display: none;">
        <h4>Upload Logs:</h4>
        <div id="logContent"></div>
    </div>

    <script>
        function addLog(message, type = 'info') {
            const logs = document.getElementById('logs');
            const content = document.getElementById('logContent');
            const timestamp = new Date().toLocaleTimeString() + '.' + new Date().getMilliseconds().toString().padStart(3, '0');

            content.innerHTML += `<div>[${timestamp}] [${type.toUpperCase()}] ${message}</div>`;
            logs.style.display = 'block';
            logs.scrollTop = logs.scrollHeight;
        }

        function uploadStandard() {
            const fileInput = document.getElementById('standardFile');
            const resultDiv = document.getElementById('standardResult');

            if (!fileInput.files[0]) {
                resultDiv.innerHTML = '<div class="error">Please select a file</div>';
                return;
            }

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            resultDiv.innerHTML = '<div class="loading">Uploading ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)...</div>';
            addLog('Starting standard upload: ' + file.name);

            fetch('/upload-standard', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <strong>✅ ${data.message}</strong><br>
                            Path: ${data.path}<br>
                            Size: ${data.size}<br>
                            Method: ${data.method}
                        </div>
                    `;
                    addLog('Standard upload completed: ' + data.size + ' - ' + data.method);
                } else {
                    resultDiv.innerHTML = '<div class="error">❌ Error: ' + data.error + '</div>';
                    addLog('Standard upload failed: ' + data.error, 'error');
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">❌ Network error: ' + error.message + '</div>';
                addLog('Standard upload network error: ' + error.message, 'error');
            });
        }

        function uploadMoveFiles() {
            const fileInput = document.getElementById('moveFilesFile');
            const resultDiv = document.getElementById('moveFilesResult');

            if (!fileInput.files[0]) {
                resultDiv.innerHTML = '<div class="error">Please select a file</div>';
                return;
            }

            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            resultDiv.innerHTML = '<div class="loading">Uploading ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB) with moveFiles()...</div>';
            addLog('Starting moveFiles upload: ' + file.name);

            fetch('/upload-movefiles', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="success">
                            <strong>🚀 ${data.message}</strong><br>
                            Path: ${data.path}<br>
                            Size: ${data.size}<br>
                            Method: ${data.method}
                        </div>
                    `;
                    addLog('MoveFiles upload completed: ' + data.size + ' - ' + data.method);
                } else {
                    resultDiv.innerHTML = '<div class="error">❌ Error: ' + data.error + '</div>';
                    addLog('MoveFiles upload failed: ' + data.error, 'error');
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="error">❌ Network error: ' + error.message + '</div>';
                addLog('MoveFiles upload network error: ' + error.message, 'error');
            });
        }

        // Initialize
        addLog('Direct upload test initialized - no Livewire dependency');
    </script>
</body>
</html>