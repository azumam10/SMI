<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        // Mengubah $form menjadi $schema, dan ->schema menjadi ->components
        return $schema
            ->components([
                Section::make('Data Jenis Cuti')
                    ->icon('heroicon-m-tag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('annual, sick, maternity'),
                        TextInput::make('name')
                            ->label('Nama Jenis Cuti')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('quota_days')
                            ->label('Kuota Hari')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(' hari'),
                        Checkbox::make('require_document')
                            ->label('Wajib Dokumen Pendukung')
                            ->default(false),
                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
