<div>

    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .upload-section { margin-bottom: 40px; padding: 25px; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; }
        .upload-section h3 { margin-top: 0; color: #333; font-size: 1.5rem; }
        .btn { background: #007cba; color: white; padding: 15px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 16px; }
        .btn:hover { background: #005a87; }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 6px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .logs-section { margin-top: 30px; padding: 20px; background: #1f2937; color: #f3f4f6; border-radius: 8px; font-family: monospace; max-height: 500px; overflow-y: auto; }
        .log-entry { margin: 5px 0; padding: 8px; border-radius: 4px; }
        .log-info { background: rgba(59, 130, 246, 0.15); }
        .log-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .log-error { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .highlight-box { background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 20px 0; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="container">
    <h1> Filament moveFiles() Test</h1>


    @if (session()->has('message'))
        <div class="alert">
            ✅ {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error">
            ❌ {{ session('error') }}
        </div>
    @endif

    <form>
        <div class="form-grid">
            <!-- Standard Filament Upload -->
            <div class="upload-section">
                <h3>📁 Standard Filament Upload</h3>
                <p><strong>Method:</strong> Default FileUpload behavior</p>
                <p><small>Standard stream copying to storage</small></p>

                <input type="file" wire:model="standardFiles" accept="*" style="margin: 10px 0; padding: 10px; border: 2px dashed #ccc; border-radius: 6px; width: 100%; box-sizing: border-box;">

                @if($standardFiles)
                    <div style="margin: 10px 0; padding: 10px; background: #f0f9ff; border-radius: 6px;">
                        <strong>Selected file:</strong>
                        <div>{{ $standardFiles->getClientOriginalName() }} ({{ number_format($standardFiles->getSize() / 1024, 1) }} KB)</div>
                    </div>
                @endif

                <div style="margin: 15px 0; padding: 10px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px;">
                    <small style="color: #0369a1;">
                        <strong>📝 Instructions:</strong>
                        1. Select files using "Choose Files" above
                        2. Click the button below to upload
                    </small>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" wire:click="submitStandard" class="btn" style="background: #007cba; font-size: 16px; padding: 15px 30px;" wire:loading.attr="disabled" wire:target="submitStandard">
                        <span wire:loading.remove wire:target="submitStandard">📤 Upload Standard Files</span>
                        <span wire:loading wire:target="submitStandard">⏳ Processing standard upload...</span>
                    </button>
                </div>
            </div>

            <!-- moveFiles Upload -->
            <div class="upload-section" style="border-color: #f59e0b; background: #fffbeb;">
                <h3>🚀 moveFiles() Upload</h3>
                <p><strong>Method:</strong> <code>FileUpload::make()->moveFiles()</code></p>
                <p><small><strong>REAL moveFiles() implementation!</strong></small></p>

                <input type="file" wire:model="moveFilesFiles" accept="*" style="margin: 10px 0; padding: 10px; border: 2px dashed #f59e0b; border-radius: 6px; width: 100%; box-sizing: border-box;">

                @if($moveFilesFiles)
                    <div style="margin: 10px 0; padding: 10px; background: #fefdf0; border-radius: 6px;">
                        <strong>Selected file:</strong>
                        <div>{{ $moveFilesFiles->getClientOriginalName() }} ({{ number_format($moveFilesFiles->getSize() / 1024, 1) }} KB)</div>
                    </div>
                @endif

                <div style="margin: 15px 0; padding: 10px; background: #fefdf0; border: 1px solid #f59e0b; border-radius: 6px;">
                    <small style="color: #92400e;">
                        <strong>📝 Instructions:</strong>
                        1. Select files using "Choose Files" above
                        2. Click the button below to upload
                    </small>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="button" wire:click="submitMoveFiles" class="btn" style="background: #f59e0b; font-size: 16px; padding: 15px 30px;" wire:loading.attr="disabled" wire:target="submitMoveFiles">
                        <span wire:loading.remove wire:target="submitMoveFiles">🔥 Upload with moveFiles()</span>
                        <span wire:loading wire:target="submitMoveFiles">⏳ Processing moveFiles() upload...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if (!empty($logs))
        <div class="logs-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0; color: #10b981;">📋 Real-time Process Logs</h3>
                <button wire:click="clearLogs" class="btn" style="background: #6b7280; padding: 8px 16px; font-size: 14px;">
                    Clear Logs
                </button>
            </div>

            @foreach ($logs as $log)
                <div class="log-entry log-{{ $log['type'] }}">
                    <span style="color: #9ca3af;">[{{ $log['timestamp'] }}]</span>
                    <span style="color: #10b981;">[{{ strtoupper($log['type']) }}]</span>
                    {{ $log['message'] }}
                </div>
            @endforeach
        </div>
    @endif

   

</div>
