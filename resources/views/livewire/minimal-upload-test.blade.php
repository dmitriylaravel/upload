<div>
    <h2>Minimal Upload Test</h2>

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

    <p>{{ $message }}</p>

    <input type="file" wire:model="testFile">

    @if($testFile)
        <p>Selected: {{ $testFile->getClientOriginalName() }}</p>
        <button wire:click="processFile">Process File</button>
    @endif

    @if (!empty($logs))
        <div style="margin-top: 20px; padding: 10px; background: #f0f0f0;">
            <h4>Logs:</h4>
            @foreach ($logs as $log)
                <div>[{{ $log['timestamp'] }}] [{{ strtoupper($log['type']) }}] {{ $log['message'] }}</div>
            @endforeach
        </div>
    @endif
</div>