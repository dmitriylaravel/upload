<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActualMoveFilesTest extends Component
{
    use WithFileUploads;

    public $standardFiles = null;
    public $moveFilesFiles = null;
    public array $logs = [];

    public function mount(): void
    {
        $this->addLog('Test initialized - comparing standard vs moveFiles() approaches');
    }

    public function updatedStandardFiles()
    {
        if ($this->standardFiles) {
            $this->addLog('Standard file updated: ' . $this->standardFiles->getClientOriginalName());
        }
    }

    public function updatedMoveFilesFiles()
    {
        if ($this->moveFilesFiles) {
            $this->addLog('MoveFiles file updated: ' . $this->moveFilesFiles->getClientOriginalName());
        }
    }

    private function addLog(string $message, string $type = 'info'): void
    {
        $this->logs[] = [
            'timestamp' => now()->format('H:i:s.v'),
            'type' => $type,
            'message' => $message
        ];
        Log::info($message);
    }

    public function submitStandard()
    {
        $this->addLog('=== PROCESSING STANDARD UPLOAD METHOD ===');

        try {
            $this->addLog('🔍 Debug: standardFiles type: ' . gettype($this->standardFiles));

            if (!$this->standardFiles) {
                $this->addLog('⚠️ No standard file selected.');
                session()->flash('error', 'Please select a file using the file chooser above before clicking this button.');
                return;
            }

            $this->addLog('🔄 Standard upload: 1 file found');
            $this->addLog("🔍 Debug: File object type: " . get_class($this->standardFiles));
            $this->addLog("✅ Processing standard file: " . $this->standardFiles->getClientOriginalName());

            // Standard store() method - copies the file
            $path = $this->standardFiles->store('large-files', 'public');
            $this->addLog("📁 Stored to: $path");

            $size = Storage::disk('public')->size($path);
            $this->addLog("📊 Size: " . number_format($size / 1024 / 1024, 2) . " MB");
            $this->addLog("📁 Method: Standard Livewire store() - copies file to final location");

            session()->flash('message', "Standard upload completed! File processed using standard copy method");

        } catch (\Exception $e) {
            $this->addLog('❌ Error processing standard upload: ' . $e->getMessage());
            $this->addLog('❌ Stack trace: ' . $e->getTraceAsString());
            session()->flash('error', 'Error processing upload: ' . $e->getMessage());
        }

        $this->addLog('=== STANDARD UPLOAD PROCESSING COMPLETE ===');
    }

    public function submitMoveFiles()
    {
        $this->addLog('=== PROCESSING MOVEFILES() UPLOAD METHOD ===');

        try {
            if (!$this->moveFilesFiles) {
                $this->addLog('⚠️ No moveFiles file selected.');
                session()->flash('error', 'Please select a file using the file chooser above before clicking this button.');
                return;
            }

            $this->addLog('⚡ moveFiles() approach: 1 file found');
            $this->addLog("✅ Processing moveFiles file: " . $this->moveFilesFiles->getClientOriginalName());

            // moveFiles() equivalent - move instead of copy
            $filename = time() . '_' . $this->moveFilesFiles->getClientOriginalName();
            $path = 'large-files/' . $filename;

            // Use move instead of copy to simulate moveFiles() behavior
            $tempPath = $this->moveFilesFiles->getRealPath();
            $finalPath = storage_path('app/public/' . $path);

            // Ensure directory exists
            $directory = dirname($finalPath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Move file instead of copying (like moveFiles() would do)
            rename($tempPath, $finalPath);

            $this->addLog("🚀 Moved to: $path");

            $size = Storage::disk('public')->size($path);
            $this->addLog("📊 Size: " . number_format($size / 1024 / 1024, 2) . " MB");
            $this->addLog("🚀 Method: MOVE operation (simulating moveFiles()) - moves file without copying");

            session()->flash('message', "moveFiles() completed! File processed using MOVE operation (no copy)");

        } catch (\Exception $e) {
            $this->addLog('❌ Error processing moveFiles upload: ' . $e->getMessage());
            session()->flash('error', 'Error processing upload: ' . $e->getMessage());
        }

        $this->addLog('=== MOVEFILES() UPLOAD PROCESSING COMPLETE ===');
    }

    public function clearLogs()
    {
        $this->logs = [];
        $this->addLog('Logs cleared');
    }

    public function render()
    {
        return view('livewire.actual-move-files-test');
    }
}
