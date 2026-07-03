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
use Filament\Schemas\Components\Utilities\Get; 
use Filament\Schemas\Components\Utilities\Set; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Services\WorkingDayCalculator;
use Filament\Notifications\Notification;

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
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => auth()->user()->employee?->id)
                            ->visible(fn () => auth()->user()->hasRole(['super_admin','hrd']))
                            ->afterStateUpdated(function ($state, Set $set) {
                                $employee = Employee::find($state);

                                if (! $employee) {
                                    return;
                                }

                                if (in_array($employee->employment_status, [
                                    'resigned',
                                    'inactive',
                                ])) {

                                    Notification::make()
                                        ->danger()
                                        ->title('Karyawan tidak dapat mengajukan cuti')
                                        ->body('Status karyawan Resign atau Non Aktif.')
                                        ->send();

                                    $set('employee_id', null);
                                }

                            })
                            ->dehydrated(),

                        Select::make('leave_type_id')
                            ->label('Jenis Cuti')
                            ->required()
                            ->options(LeaveType::where('is_active', true)->pluck('name', 'id'))
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            $set('documents', []);

                            $employeeId = $get('employee_id');

                            if (! $employeeId) {
                                return;
                            }

                            $balance = LeaveBalance::query()
                                ->where('employee_id', $employeeId)
                                ->where('leave_type_id', $state)
                                ->where('year', now()->year)
                                ->first();

                            if (! $balance) {

                                Notification::make()
                                    ->warning()
                                    ->title('Saldo cuti belum tersedia')
                                    ->send();

                            }

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
                            ->rule('after_or_equal:start_date')
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
               private static function calculateTotalDays(
                Get $get,
                Set $set
            ): void {

                $start = $get('start_date');
                $end = $get('end_date');

                if (! $start || ! $end) {
                    $set('total_days', null);
                    return;
                }

                $calculator = app(WorkingDayCalculator::class);

                $days = $calculator->calculate(
                    Carbon::parse($start),
                    Carbon::parse($end)
                );

                $set('total_days', $days);

                $employeeId = $get('employee_id');
                $leaveTypeId = $get('leave_type_id');

                if (! $employeeId || ! $leaveTypeId) {
                    return;
                }

                $balance = LeaveBalance::query()
                    ->where('employee_id', $employeeId)
                    ->where('leave_type_id', $leaveTypeId)
                    ->where('year', now()->year)
                    ->first();

                if (! $balance) {
                    return;
                }

                if ($days > $balance->remaining) {

                    Notification::make()
                        ->danger()
                        ->title('Saldo cuti tidak mencukupi')
                        ->body("Sisa cuti hanya {$balance->remaining} hari.")
                        ->send();

                }

            }
}