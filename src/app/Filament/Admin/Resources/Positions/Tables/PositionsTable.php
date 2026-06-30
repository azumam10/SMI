<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Positions\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;

final class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nama Jabatan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('Level')
                    ->badge()
                    ->colors([
                        'danger' => 'Direktur',
                        'warning' => 'Manager',
                        'info' => 'Kepala Bagian',
                        'primary' => 'Supervisor',
                        'gray' => ['Staff', 'Operator', 'Security', 'Lainnya'],
                    ])
                    ->sortable(),
                IconColumn::make('has_subordinates')
                    ->label('Bawahan')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->options([
                        'Direktur' => 'Direktur',
                        'Manager' => 'Manager',
                        'Kepala Bagian' => 'Kepala Bagian',
                        'Supervisor' => 'Supervisor',
                        'Staff' => 'Staff',
                        'Operator' => 'Operator',
                        'Security' => 'Security',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Aktif',
                        '0' => 'Nonaktif',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                DeleteBulkAction::make(),
                ]),
            ]);
    }
}