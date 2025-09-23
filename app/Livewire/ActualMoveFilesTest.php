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

    public $standardFiles = [];
    public $moveFilesFiles = [];
    public array $logs = [];

    public function mount(): void
    {
        $this->addLog('Test initialized - comparing standard vs moveFiles() approaches');
    }

    public function updatedStandardFiles()
    {
        $this->addLog('Standard files updated: ' . count($this->standardFiles) . ' files');
    }

    public function updatedMoveFilesFiles()
    {
        $this->addLog('MoveFiles files updated: ' . count($this->moveFilesFiles) . ' files');
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
            if (empty($this->standardFiles)) {
                $this->addLog('⚠️ No standard files selected.');
                session()->flash('error', 'Please select files using the file chooser above before clicking this button.');
                return;
            }

            $this->addLog('🔄 Standard upload: ' . count($this->standardFiles) . ' files found');

            foreach ($this->standardFiles as $index => $file) {
                $this->addLog("✅ Processing standard file #$index: " . $file->getClientOriginalName());

                // Standard store() method - copies the file
                $path = $file->store('large-files', 'public');
                $this->addLog("📁 Stored to: $path");

                $size = Storage::disk('public')->size($path);
                $this->addLog("📊 Size: " . number_format($size / 1024 / 1024, 2) . " MB");
                $this->addLog("📁 Method: Standard Livewire store() - copies file to final location");
            }

            session()->flash('message', "Standard upload completed! " . count($this->standardFiles) . " files processed using standard copy method");

        } catch (\Exception $e) {
            $this->addLog('❌ Error processing standard upload: ' . $e->getMessage());
            session()->flash('error', 'Error processing upload: ' . $e->getMessage());
        }

        $this->addLog('=== STANDARD UPLOAD PROCESSING COMPLETE ===');
    }

    public function submitMoveFiles()
    {
        $this->addLog('=== PROCESSING MOVEFILES() UPLOAD METHOD ===');

        try {
            if (empty($this->moveFilesFiles)) {
                $this->addLog('⚠️ No moveFiles files selected.');
                session()->flash('error', 'Please select files using the file chooser above before clicking this button.');
                return;
            }

            $this->addLog('⚡ moveFiles() approach: ' . count($this->moveFilesFiles) . ' files found');

            foreach ($this->moveFilesFiles as $index => $file) {
                $this->addLog("✅ Processing moveFiles file #$index: " . $file->getClientOriginalName());

                // moveFiles() equivalent - move instead of copy
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = 'large-files/' . $filename;

                // Use move instead of copy to simulate moveFiles() behavior
                $tempPath = $file->getRealPath();
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
            }

            session()->flash('message', "moveFiles() completed! " . count($this->moveFilesFiles) . " files processed using MOVE operation (no copy)");

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
        return view('livewire.actual-move-files-test')
            ->layout('components.layouts.app')
            ->layoutData(['title' => 'ACTUAL Filament moveFiles() Test']);
    }
}
