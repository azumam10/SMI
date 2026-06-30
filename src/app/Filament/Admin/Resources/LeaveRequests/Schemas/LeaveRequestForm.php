<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests\Schemas;

use App\Models\LeaveType;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get; // 🟢 Diubah ke namespace Filament\Schemas
use Filament\Schemas\Components\Utilities\Set; // 🟢 Diubah ke namespace Filament\Schemas
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

final class LeaveRequestForm
{
    /**
     * Configure the Leave Request form schema definition.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                // Section: Core Leave Request Information
                Section::make('Informasi Pengajuan Cuti')
                    ->icon('heroicon-m-calendar-days')
                    ->columns(2)
                    ->schema([
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->required()
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => Auth::user()->employee?->id)
                            ->hidden(fn () => !Auth::user()->hasRole(['super_admin', 'hrd']))
                            ->disabled(fn () => !Auth::user()->hasRole(['super_admin', 'hrd'])),

                        Select::make('leave_type_id')
                            ->label('Jenis Cuti')
                            ->required()
                            ->options(LeaveType::where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('documents', []);
                            }),

                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->minDate(today())
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state && $get('end_date')) {
                                    $start = Carbon::parse($state);
                                    $end = Carbon::parse($get('end_date'));
                                    if ($end->lt($start)) {
                                        $set('end_date', null);
                                    }
                                    self::calculateTotalDays($get, $set);
                                }
                            }),

                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->minDate(fn (Get $get) => $get('start_date') ?? today())
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state && $get('start_date')) {
                                    self::calculateTotalDays($get, $set);
                                }
                            }),

                        TextInput::make('total_days')
                            ->label('Total Hari')
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->suffix(' hari')
                            ->helperText('Otomatis dihitung berdasarkan rentang tanggal'),

                        Textarea::make('reason')
                            ->label('Alasan Cuti')
                            ->required()
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),

                // Section: Supporting Attachments
                Section::make('Dokumen Pendukung')
                    ->icon('heroicon-m-paper-clip')
                    ->schema([
                        Repeater::make('documents')
                            ->label('Upload File')
                            ->relationship('documents')
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::processDocumentMetadata($data))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::processDocumentMetadata($data))
                            ->schema([
                                FileUpload::make('file')
                                    ->label('File')
                                    ->required()
                                    ->disk('public')
                                    ->directory('leave-documents')
                                    ->visibility('public')
                                    ->maxSize(5120)
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'image/jpg',
                                    ])
                                    ->storeFileNamesIn('original_name')
                                    ->helperText('Format: PDF, JPG, PNG. Maks 5MB.'),
                            ])
                            ->columns(1)
                            ->defaultItems(0)
                            ->maxItems(3)
                            ->itemLabel(fn (array $state): ?string => $state['original_name'] ?? 'File')
                            ->collapsible()
                            ->addActionLabel('Tambah File')
                            ->visible(function (Get $get) {
                                $type = LeaveType::find($get('leave_type_id'));
                                return $type && $type->require_document;
                            })
                            ->required(function (Get $get) {
                                $type = LeaveType::find($get('leave_type_id'));
                                return $type && $type->require_document;
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(fn (Get $get) => !$get('leave_type_id')),

                // Section: Approval Status
                Section::make('Approval')
                    ->icon('heroicon-m-check-badge')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Menunggu',
                                'supervisor_approved' => 'Disetujui Atasan',
                                'supervisor_rejected' => 'Ditolak Atasan',
                                'hrd_approved' => 'Disetujui HRD',
                                'hrd_rejected' => 'Ditolak HRD',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->disabled()
                            ->visible(fn ($record) => $record !== null),

                        Textarea::make('supervisor_note')
                            ->label('Catatan Atasan')
                            ->rows(2)
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->supervisor_id),

                        Textarea::make('hrd_note')
                            ->label('Catatan HRD')
                            ->rows(2)
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->hrd_id),
                    ])
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    /**
     * Process and inject metadata for the uploaded document.
     */
    private static function processDocumentMetadata(array $data): array
    {
        if (! empty($data['file'])) {
            $data['path'] = $data['file'];
            $data['stored_name'] = basename($data['file']);
            $data['disk'] = 'public';
            $data['uploaded_by'] = Auth::id();

            $storage = Storage::disk('public');
            
            if ($storage->exists($data['file'])) {
                $data['mime_type'] = $storage->mimeType($data['file']);
                $data['size'] = $storage->size($data['file']);
            }
        }

        return $data;
    }

    /**
     * Calculate total leave days based on date range input.
     */
    private static function calculateTotalDays(Get $get, Set $set): void
    {
        $start = $get('start_date');
        $end = $get('end_date');
        
        if ($start && $end) {
            $startDate = Carbon::parse($start);
            $endDate = Carbon::parse($end);
            
            if ($endDate->gte($startDate)) {
                $days = $startDate->diffInDays($endDate) + 1;
                $set('total_days', $days);
            }
        }
    }
}