<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopPerformanceTable extends TableWidget
{
    protected static ?string $heading = '🏆 Top 10 Karyawan';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        return $table
            ->query(
                PerformanceReview::query()
                    ->where('status', PerformanceReview::STATUS_APPROVED)
                    ->where('year', $year)
                    ->where('semester', $semester)
                    ->with('employee.department')
                    ->orderByDesc('final_score')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width(40),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Departemen')
                    ->sortable(),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Outstanding' => 'success',
                        'Excellent'   => 'info',
                        'Good'        => 'warning',
                        'Fair'        => 'gray',
                        'Poor'        => 'danger',
                        default       => 'gray',
                    }),
            ])
            ->defaultSort('final_score', 'desc')
            ->poll('10s'); // refresh tiap 10 detik
    }
}