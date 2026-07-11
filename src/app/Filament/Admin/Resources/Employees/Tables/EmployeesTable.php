<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id_number')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->description(fn ($record) => $record->position?->name ?? '-'),

                TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('section.name')
                    ->label('Bagian')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status_karyawan')
                    ->label('Status')
                    ->badge()
                    // Filament v5: ->colors() sudah dihapus, pakai ->color(fn)
                    ->color(fn (string $state) => match ($state) {
                        'PKWTT' => 'primary',
                        'PKWT' => 'warning',
                        'HARIAN' => 'gray',
                        'DIREKTUR' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('gender')
                    ->label('L/P')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'L' ? 'Laki-laki' : 'Perempuan')
                    ->color(fn ($state) => $state === 'L' ? 'info' : 'pink')
                    ->toggleable(),

                TextColumn::make('usia')
                    ->label('Usia')
                    ->sortable()
                    ->suffix(' thn')
                    ->toggleable(),

                TextColumn::make('generation')
                    ->label('Generasi')
                    ->badge()
                    // Filament v5: ->colors() sudah dihapus, pakai ->color(fn)
                    ->color(fn ($state) => match ($state) {
                        'Gen Z' => 'info',
                        'Milenial' => 'success',
                        'Gen X' => 'warning',
                        'Baby Boomers' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('hire_date')
                    ->label('Bergabung')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // masa_kerja adalah PHP accessor, tidak bisa ->sortable()
                // karena bukan kolom database — dihapus sortable()
                TextColumn::make('masa_kerja')
                    ->label('Masa Kerja')
                    ->getStateUsing(fn ($record) => $record->masa_kerja)
                    ->toggleable(),

                TextColumn::make('performance_category')
                    ->label('Kinerja')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'High' => 'success',
                        'Med' => 'warning',
                        'Low' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?? '—')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('section_id')
                    ->label('Bagian')
                    ->relationship('section', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status_karyawan')
                    ->label('Status')
                    ->options([
                        'PKWTT' => 'PKWTT (Tetap)',
                        'PKWT' => 'PKWT (Kontrak)',
                        'HARIAN' => 'Harian',
                        'DIREKTUR' => 'Direktur',
                    ]),

                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),

                SelectFilter::make('performance_category')
                    ->label('Kinerja')
                    ->options([
                        'High' => 'High',
                        'Med' => 'Medium',
                        'Low' => 'Low',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
