<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Log;

class RealMoveFilesTest extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $standardFiles = [];
    public ?array $moveFiles = [];
    public array $logs = [];

    public function mount(): void
    {
        $this->addLog('Real moveFiles() test initialized - using actual Filament FileUpload components');
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Standard Upload (temp files)')
                    ->description('Uses standard Livewire file upload with temporary files')
                    ->schema([
                        FileUpload::make('standardFiles')
                            ->label('Standard Upload')
                            ->disk('public')
                            ->directory('standard-uploads')
                            ->maxSize(50000) // 50MB
                            ->acceptedFileTypes(['*'])
                            ->live()
                    ]),

                Section::make('MoveFiles Upload (direct move)')
                    ->description('Uses Filament moveFiles() - bypasses temp files for large uploads')
                    ->schema([
                        FileUpload::make('moveFiles')
                            ->label('MoveFiles Upload')
                            ->disk('public')
                            ->directory('move-uploads')
                            ->moveFiles() // This is the key method!
                            ->maxSize(2000000) // 2GB
                            ->acceptedFileTypes(['*'])
                            ->live()
                    ])
            ])
            ->statePath('data');
    }

    public function updatedData($value, $key): void
    {
        if ($key === 'standardFiles') {
            $this->addLog('Standard files updated: ' . count($this->data['standardFiles'] ?? []) . ' files');
            foreach (($this->data['standardFiles'] ?? []) as $file) {
                if (is_string($file)) {
                    $this->addLog("📁 Standard file: $file");
                }
            }
        }

        if ($key === 'moveFiles') {
            $this->addLog('MoveFiles updated: ' . count($this->data['moveFiles'] ?? []) . ' files');
            foreach (($this->data['moveFiles'] ?? []) as $file) {
                if (is_string($file)) {
                    $this->addLog("🚀 MoveFiles file: $file (processed with moveFiles())");
                }
            }
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

    public function clearLogs()
    {
        $this->logs = [];
        $this->addLog('Logs cleared');
    }

    public function render()
    {
        return view('livewire.real-move-files-test');
    }
}