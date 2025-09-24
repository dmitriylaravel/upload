<div>
    <h2>Real moveFiles() Test</h2>

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

    <div style="margin-bottom: 20px;">
        <p><strong>Testing Real Filament moveFiles():</strong></p>
        <ul>
            <li><strong>Standard Upload</strong>: Uses temporary files (limited by PHP upload limits)</li>
            <li><strong>MoveFiles Upload</strong>: Bypasses temp files with <code>->moveFiles()</code> method</li>
        </ul>
    </div>

    {{ $this->form }}

    @if (!empty($logs))
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
            <h4>Logs:</h4>
            @foreach ($logs as $log)
                <div>[{{ $log['timestamp'] }}] [{{ strtoupper($log['type']) }}] {{ $log['message'] }}</div>
            @endforeach
            <button wire:click="clearLogs" style="margin-top: 10px;">Clear Logs</button>
        </div>
    @endif
</div>