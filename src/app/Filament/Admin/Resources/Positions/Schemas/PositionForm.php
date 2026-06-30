<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Positions\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

final class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Jabatan')
                    ->icon('heroicon-m-briefcase')
                    ->columns(2)
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode Jabatan')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: HRD-MGR, IT-SPV'),
                        TextInput::make('name')
                            ->label('Nama Jabatan')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Manager HRD, Supervisor IT'),
                        Select::make('level')
                            ->label('Level Jabatan')
                            ->required()
                            ->options([
                                'Direktur' => 'Direktur',
                                'Manager' => 'Manager',
                                'Kepala Bagian' => 'Kepala Bagian',
                                'Supervisor' => 'Supervisor',
                                'Staff' => 'Staff',
                                'Operator' => 'Operator',
                                'Security' => 'Security',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Staff'),
                        Toggle::make('has_subordinates')
                            ->label('Memiliki Bawahan')
                            ->default(false)
                            ->helperText('Centang jika jabatan ini memiliki wewenang menyetujui cuti/penilaian bawahan'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }
}