<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Informasi Pengajuan')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('employee.name')
                                    ->label('Karyawan'),

                                TextEntry::make('leaveType.name')
                                    ->label('Jenis Cuti'),

                                TextEntry::make('submitted_at')
                                    ->label('Tanggal Pengajuan')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->label('Status')
                                    ->color(fn ($record) => $record->status_color)
                                    ->formatStateUsing(fn ($record) => $record->status_label),

                                TextEntry::make('start_date')
                                    ->label('Mulai')
                                    ->date('d M Y'),

                                TextEntry::make('end_date')
                                    ->label('Selesai')
                                    ->date('d M Y'),

                                TextEntry::make('total_days')
                                    ->label('Total Hari')
                                    ->suffix(' Hari'),

                                TextEntry::make('approved_days')
                                    ->label('Hari Disetujui')
                                    ->suffix(' Hari')
                                    ->placeholder('-'),

                            ]),

                        TextEntry::make('reason')
                            ->label('Alasan Cuti')
                            ->columnSpanFull(),

                    ]),

                Section::make('Persetujuan Kepala Bagian')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('supervisor.name')
                                    ->label('Kepala Bagian')
                                    ->placeholder('-'),

                                TextEntry::make('supervisor_approved_at')
                                    ->label('Tanggal Approval')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                            ]),

                        TextEntry::make('supervisor_note')
                            ->label('Catatan')
                            ->placeholder('-'),

                    ])
                    ->collapsible(),

                Section::make('Persetujuan HRD')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('hrd.name')
                                    ->label('HRD')
                                    ->placeholder('-'),

                                TextEntry::make('hrd_approved_at')
                                    ->label('Tanggal Approval')
                                    ->dateTime('d M Y H:i')
                                    ->placeholder('-'),

                            ]),

                        TextEntry::make('hrd_note')
                            ->label('Catatan')
                            ->placeholder('-'),

                    ])
                    ->collapsible(),

                Section::make('Dokumen Pendukung')
                    ->schema([

                        RepeatableEntry::make('documents')
                            ->hidden(fn ($record) => $record->documents->isEmpty())
                            ->schema([

                                TextEntry::make('original_name')
                                    ->label('Nama File'),

                                TextEntry::make('formatted_size')
                                    ->label('Ukuran'),

                                TextEntry::make('mime_type')
                                    ->label('Tipe'),

                                TextEntry::make('created_at')
                                    ->label('Upload')
                                    ->dateTime('d M Y H:i'),

                                TextEntry::make('download_url')
                                    ->label('Download')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab(),

                            ]),

                        TextEntry::make('documents_empty')
                            ->label('')
                            ->state('Tidak ada dokumen yang dilampirkan.')
                            ->visible(fn ($record) => $record->documents->isEmpty()),

                    ])
                    ->collapsible(),

                Section::make('Informasi Sistem')
                    ->schema([

                        Grid::make(2)
                            ->schema([

                                TextEntry::make('created_at')
                                    ->label('Dibuat')
                                    ->dateTime('d M Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Diubah')
                                    ->dateTime('d M Y H:i'),

                            ]),

                    ])
                    ->collapsed(),

            ]);
    }
}
