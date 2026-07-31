<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

final class ActivityLogRelationManager extends RelationManager
{
    // Relasi 'activities' disediakan oleh package spatie/laravel-activitylog
    // yang sudah ter-include via jacobtims/filament-logger
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Riwayat Perubahan';

    // Filament v5: $icon harus BackedEnum|string|null — pakai Heroicon enum
    protected static string|BackedEnum|null $icon = Heroicon::Clock;

    // Tidak ada form — log hanya dibaca, tidak dibuat manual
    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->width('160px'),

                TextColumn::make('causer.name')
                    ->label('Diubah Oleh')
                    ->placeholder('Sistem')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('event')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                        default => $state,
                    }),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(),

                TextColumn::make('properties')
                    ->label('Perubahan')
                    ->getStateUsing(function ($record) {
                        $props = $record->properties;
                        if (empty($props) || ! isset($props['old'], $props['attributes'])) {
                            return '—';
                        }

                        $changed = [];
                        foreach ($props['attributes'] as $key => $newVal) {
                            $oldVal = $props['old'][$key] ?? null;
                            if ($oldVal !== $newVal) {
                                $changed[] = "{$key}: {$oldVal} → {$newVal}";
                            }
                        }

                        return implode(', ', array_slice($changed, 0, 3))
                            .(count($changed) > 3 ? ' ...' : '');
                    })
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Dibuat',
                        'updated' => 'Diperbarui',
                        'deleted' => 'Dihapus',
                    ]),
            ])
            ->headerActions([]) // log tidak dibuat manual
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon('heroicon-o-clock')
            ->emptyStateHeading('Belum ada riwayat')
            ->emptyStateDescription('Perubahan data karyawan akan tercatat di sini.');
    }
}
