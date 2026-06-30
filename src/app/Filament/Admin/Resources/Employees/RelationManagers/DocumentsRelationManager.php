<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use App\Models\DocumentFile;
use App\Models\EmployeeDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Dokumen Karyawan';

    // Filament v5: $icon harus BackedEnum|string|null — pakai Heroicon enum
    protected static string|\BackedEnum|null $icon = Heroicon::DocumentText;

    // ──────────────────────────────────────────────────────────────────
    // FORM: tambah / edit grup dokumen
    // ──────────────────────────────────────────────────────────────────
    public function form(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Informasi Dokumen')
                ->columns(2)
                ->schema([
                    Select::make('category')
                        ->label('Kategori Dokumen')
                        ->required()
                        ->options(EmployeeDocument::CATEGORY_LABELS)
                        ->searchable()
                        ->disabledOn('edit')
                        ->helperText('Setiap karyawan hanya bisa punya 1 entri per kategori'),

                    TextInput::make('label')
                        ->label('Keterangan Singkat')
                        ->maxLength(255)
                        ->placeholder('Contoh: Kontrak 2024-2026')
                        ->helperText('Opsional — untuk membedakan dokumen sejenis'),
                ]),

            Section::make('Catatan')
                ->schema([
                    Textarea::make('keterangan')
                        ->label('Keterangan Tambahan')
                        ->rows(2)
                        ->maxLength(1000),
                ]),

            // ── Upload file (max 5) ───────────────────────────────────
            Section::make('File Dokumen')
                ->description('Maksimal 5 file per kategori. Format: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX')
                ->schema([
                    FileUpload::make('uploaded_files')
                        ->label('Upload File')
                        ->multiple()
                        ->maxFiles(5)
                        ->maxSize(10240) // 10MB per file
                        ->acceptedFileTypes([
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->disk('public')
                        ->directory(fn ($record) => 'employees/' . ($record?->employee_id ?? 'temp') . '/documents')
                        ->storeFileNamesIn('original_file_names')
                        ->reorderable()
                        ->appendFiles()
                        ->downloadable()
                        ->previewable()
                        ->helperText('Drag & drop atau klik untuk upload. Maks 10MB per file.'),
                ]),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // TABLE: daftar dokumen per karyawan
    // ──────────────────────────────────────────────────────────────────
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('category')
            ->columns([
                TextColumn::make('category_label')
                    ->label('Kategori')
                    ->getStateUsing(fn ($record) => $record->category_label)
                    ->icon('heroicon-m-document-text')
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->searchable(query: fn ($query, $search) => $query->where('category', 'like', "%{$search}%")),

                TextColumn::make('label')
                    ->label('Keterangan')
                    ->placeholder('—')
                    ->limit(40),

                TextColumn::make('files_count')
                    ->label('Jumlah File')
                    ->getStateUsing(fn ($record) => $record->files()->count())
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'danger',
                        $state >= 5  => 'warning',
                        default      => 'success',
                    })
                    ->suffix(fn ($state) => ' / 5'),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Dokumen')
                    ->icon('heroicon-m-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Validasi: kategori belum ada untuk karyawan ini
                        return $data;
                    })
                    ->using(function (array $data, string $model): Model {
                        // Ambil employee_id dari owner record
                        $employeeId = $this->getOwnerRecord()->id;

                        // Cek apakah kategori ini sudah ada
                        $existing = EmployeeDocument::where('employee_id', $employeeId)
                            ->where('category', $data['category'])
                            ->first();

                        if ($existing) {
                            Notification::make()
                                ->title('Kategori sudah ada')
                                ->body('Karyawan ini sudah memiliki dokumen ' . EmployeeDocument::CATEGORY_LABELS[$data['category']] . '. Edit dokumen yang sudah ada.')
                                ->warning()
                                ->send();

                            return $existing;
                        }

                        // Buat EmployeeDocument
                        $document = EmployeeDocument::create([
                            'employee_id' => $employeeId,
                            'category'    => $data['category'],
                            'label'       => $data['label'] ?? null,
                            'keterangan'  => $data['keterangan'] ?? null,
                        ]);

                        // Simpan file-file yang diupload
                        if (! empty($data['uploaded_files'])) {
                            $this->saveUploadedFiles($document, $data['uploaded_files']);
                        }

                        return $document;
                    }),
            ])
            ->actions([
                // Lihat & download file
                Action::make('lihat_file')
                    ->label('File')
                    ->icon('heroicon-m-paper-clip')
                    ->color('info')
                    ->modalHeading(fn ($record) => 'File — ' . $record->category_label)
                    ->modalContent(fn ($record) => view(
                        'filament.modals.document-files',
                        ['document' => $record->load('files')]
                    ))
                    ->modalWidth('2xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        $record->update([
                            'label'      => $data['label'] ?? null,
                            'keterangan' => $data['keterangan'] ?? null,
                        ]);

                        if (! empty($data['uploaded_files'])) {
                            // Cek kapasitas
                            $currentCount = $record->files()->count();
                            $newFiles = count($data['uploaded_files']);

                            if ($currentCount + $newFiles > 5) {
                                Notification::make()
                                    ->title('Batas file tercapai')
                                    ->body("Sudah ada {$currentCount} file. Maksimal 5 file per kategori.")
                                    ->warning()
                                    ->send();
                            } else {
                                $this->saveUploadedFiles($record, $data['uploaded_files'], $currentCount);
                            }
                        }

                        return $record;
                    }),

                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Semua file dalam kategori ini akan ikut dihapus permanen.'),
            ])
            ->defaultSort('category')
            ->emptyStateIcon('heroicon-o-document')
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateDescription('Klik "Tambah Dokumen" untuk mengunggah dokumen karyawan.');
    }

    // ──────────────────────────────────────────────────────────────────
    // HELPER: simpan file ke storage & catat ke document_files
    // ──────────────────────────────────────────────────────────────────
    private function saveUploadedFiles(
        EmployeeDocument $document,
        array $filePaths,
        int $startOrder = 0
    ): void {
        foreach ($filePaths as $index => $path) {
            // path sudah disimpan oleh FileUpload ke disk 'public'
            $fullPath = storage_path('app/public/' . $path);
            $mimeType = file_exists($fullPath)
                ? mime_content_type($fullPath)
                : 'application/octet-stream';
            $size = file_exists($fullPath) ? filesize($fullPath) : 0;
            $originalName = basename($path);

            DocumentFile::create([
                'employee_document_id' => $document->id,
                'original_name'        => $originalName,
                'stored_name'          => basename($path),
                'disk'                 => 'public',
                'path'                 => $path,
                'mime_type'            => $mimeType,
                'size'                 => $size,
                'sort_order'           => $startOrder + $index,
                'uploaded_by'          => auth()->id(),
                ]);
        }
    }
}