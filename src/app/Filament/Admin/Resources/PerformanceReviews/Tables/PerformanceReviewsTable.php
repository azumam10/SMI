<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PerformanceReviews\Tables;

use App\Models\PerformanceReview;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class PerformanceReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Penilai')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('semester_label')
                    ->label('Periode')
                    ->getStateUsing(fn ($record) => "Semester {$record->semester} / {$record->year}")
                    ->sortable(query: function (Builder $query, string $direction) {
                        return $query->orderBy('year', $direction)
                                     ->orderBy('semester', $direction);
                    }),

                TextColumn::make('final_score')
                    ->label('Nilai Akhir')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->getStatusBadgeColor())
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel()),

                TextColumn::make('approver.name')
                    ->label('Disetujui Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('Tgl. Approve')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Draft',
                        'submitted' => 'Menunggu Approval',
                        'approved'  => 'Disetujui',
                        'revised'   => 'Revisi',
                    ]),

                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(fn () => PerformanceReview::distinct()
                        ->orderBy('year', 'desc')
                        ->pluck('year', 'year')
                        ->toArray()
                    ),

                SelectFilter::make('semester')
                    ->label('Semester')
                    ->options([
                        1 => 'Semester 1',
                        2 => 'Semester 2',
                    ]),
            ])
            ->searchable()
            ->searchPlaceholder('Cari karyawan atau penilai...')
            ->defaultSort('year', 'desc')
            ->defaultSort('semester', 'desc');
    }
}