<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Log;

class MinimalUploadTest extends Component
{
    use WithFileUploads;

    public $testFile = null;
    public $message = 'Select a file';
    public array $logs = [];

    public function updatedTestFile()
    {
        if ($this->testFile) {
            $this->message = 'File selected: ' . $this->testFile->getClientOriginalName();
            $this->addLog('File selected: ' . $this->testFile->getClientOriginalName());
        }
    }

    public function processFile()
    {
        if ($this->testFile) {
            $this->addLog('Processing file: ' . $this->testFile->getClientOriginalName());

            // Add session flash like in the original
            session()->flash('message', 'File processed successfully!');

            $this->addLog('File processing complete');
        } else {
            session()->flash('error', 'No file selected');
        }
    }

    private function addLog(string $message): void
    {
        $this->logs[] = [
            'timestamp' => now()->format('H:i:s.v'), // More precise timestamp like original
            'type' => 'info',
            'message' => $message
        ];
        Log::info($message); // Laravel Log facade like original
    }

    public function render()
    {
        return view('livewire.minimal-upload-test');
    }
}