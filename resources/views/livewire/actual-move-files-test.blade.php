<div>
    <h2>File Upload Test</h2>

    @if (session()->has('message'))
        <div style="color: green; padding: 10px; background: #d4edda;">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div style="color: red; padding: 10px; background: #f8d7da;">
            {{ session('error') }}
        </div>
    @endif

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div style="border: 1px solid #ccc; padding: 20px;">
            <h3>Standard Upload</h3>
            <input type="file" wire:model="standardFiles">
            @if($standardFiles)
                <p>Selected: {{ $standardFiles->getClientOriginalName() }}</p>
                <button wire:click="submitStandard">Upload Standard</button>
            @endif
        </div>

        <div style="border: 1px solid #ccc; padding: 20px;">
            <h3>MoveFiles Upload</h3>
            <input type="file" wire:model="moveFilesFiles">
            @if($moveFilesFiles)
                <p>Selected: {{ $moveFilesFiles->getClientOriginalName() }}</p>
                <button wire:click="submitMoveFiles">Upload MoveFiles</button>
            @endif
        </div>
    </div>

    @if (!empty($logs))
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
            <h4>Logs:</h4>
            @foreach ($logs as $log)
                <div>[{{ $log['timestamp'] }}] [{{ strtoupper($log['type']) }}] {{ $log['message'] }}</div>
            @endforeach
            <button wire:click="clearLogs">Clear Logs</button>
        </div>
    @endif
</div>