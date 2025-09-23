<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ActualMoveFilesTest extends Component implements HasForms
{
    use WithFileUploads, InteractsWithForms;

    public ?array $data = [];
    public array $logs = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->addLog('Test initialized - comparing default vs ACTUAL moveFiles()');
    }

    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('standard_files')
                ->label('Standard Filament Upload')
                ->directory('large-files')
                ->maxSize(2048000) // 2GB
                ->multiple()
                ->acceptedFileTypes(['*'])
                ->helperText('Standard Filament upload behavior'),

            FileUpload::make('movefiles_files')
                ->label('Filament Upload with moveFiles()')
                ->moveFiles() // THIS IS THE ACTUAL MOVEFILES
                ->directory('large-files')
                ->maxSize(2048000) // 2GB
                ->multiple()
                ->acceptedFileTypes(['*'])
                ->helperText('ACTUAL Filament moveFiles() implementation'),
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'data';
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
        $data = $this->form->getState();

        // Process standard files only
        if (isset($data['standard_files']) && !empty($data['standard_files'])) {
            $this->addLog('🔄 Standard upload: ' . count($data['standard_files']) . ' files');
            foreach ($data['standard_files'] as $index => $filePath) {
                $this->addLog("✅ Standard file #$index: $filePath");
                if (Storage::exists($filePath)) {
                    $size = Storage::size($filePath);
                    $this->addLog("📊 Size: " . number_format($size / 1024 / 1024, 2) . " MB");
                    $this->addLog("📁 Method: Standard Filament upload (copies stream)");
                }
            }
            session()->flash('message', "Standard upload completed! " . count($data['standard_files']) . " files processed");
        } else {
            session()->flash('error', 'No files were selected for standard upload');
        }

        $this->addLog('=== STANDARD UPLOAD PROCESSING COMPLETE ===');
    }

    public function submitMoveFiles()
    {
        $this->addLog('=== PROCESSING MOVEFILES() UPLOAD METHOD ===');
        $data = $this->form->getState();

        // Process moveFiles only
        if (isset($data['movefiles_files']) && !empty($data['movefiles_files'])) {
            $this->addLog('⚡ ACTUAL moveFiles(): ' . count($data['movefiles_files']) . ' files');
            foreach ($data['movefiles_files'] as $index => $filePath) {
                $this->addLog("✅ moveFiles() file #$index: $filePath");
                if (Storage::exists($filePath)) {
                    $size = Storage::size($filePath);
                    $this->addLog("📊 Size: " . number_format($size / 1024 / 1024, 2) . " MB");
                    $this->addLog("🚀 Method: ACTUAL Filament moveFiles() - should move without copying");
                }
            }
            session()->flash('message', "moveFiles() upload completed! " . count($data['movefiles_files']) . " files processed");
        } else {
            session()->flash('error', 'No files were selected for moveFiles() upload');
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
