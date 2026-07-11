<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Sections\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Bagian')
                    ->icon('heroicon-m-squares-2x2')
                    ->columns(2)
                    ->schema([
                        Select::make('department_id')
                            ->label('Departemen')
                            ->required()
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('code')->required()->maxLength(10),
                                TextInput::make('name')->required()->maxLength(255),
                            ]),
                        TextInput::make('code')
                            ->label('Kode Bagian')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: HRD-REC, IT-DEV'),
                        TextInput::make('name')
                            ->label('Nama Bagian')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Recruitment, Development'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}
