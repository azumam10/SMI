<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubordinatesRelationManager extends RelationManager
{
    protected static string $relationship = 'subordinates';

    protected static ?string $title = 'Bawahan Langsung';

    // Filament v5: $icon harus BackedEnum|string|null — pakai Heroicon enum
    protected static string|\BackedEnum|null $icon = Heroicon::UserGroup;

    // RelationManager ini hanya tampil jika karyawan punya bawahan
    public static function canViewForRecord(
        \Illuminate\Database\Eloquent\Model $ownerRecord,
        string $pageClass
    ): bool {
        return $ownerRecord->subordinates()->exists()
            || ($ownerRecord->position?->has_subordinates ?? false);
    }

    // Tidak perlu form — bawahan tidak dibuat dari sini, hanya dilihat
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('id_number')
                    ->label('NIK')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->width('120px'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->description(fn ($record) => $record->section?->name ?? '-'),

                TextColumn::make('position.name')
                    ->label('Jabatan')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status_karyawan')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'PKWTT'    => 'primary',
                        'PKWT'     => 'warning',
                        'HARIAN'   => 'gray',
                        'DIREKTUR' => 'danger',
                        default    => 'gray',
                    }),

                TextColumn::make('masa_kerja')
                    ->label('Masa Kerja')
                    ->getStateUsing(fn ($record) => $record->masa_kerja)
                    ->toggleable(),

                TextColumn::make('performance_category')
                    ->label('Kinerja')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'High' => 'success',
                        'Med'  => 'warning',
                        'Low'  => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?? '—'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status_karyawan')
                    ->label('Status')
                    ->options([
                        'PKWTT'  => 'PKWTT (Tetap)',
                        'PKWT'   => 'PKWT (Kontrak)',
                        'HARIAN' => 'Harian',
                    ]),

                SelectFilter::make('performance_category')
                    ->label('Kinerja')
                    ->options([
                        'High' => 'High',
                        'Med'  => 'Medium',
                        'Low'  => 'Low',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat Profil')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.employees.view',
                        ['record' => $record]
                    )),
            ])
            ->headerActions([]) // tidak bisa tambah bawahan dari sini
            ->bulkActions([])   // tidak ada bulk action
            ->defaultSort('name')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('Tidak ada bawahan')
            ->emptyStateDescription('Karyawan ini belum memiliki bawahan langsung.');
    }
}