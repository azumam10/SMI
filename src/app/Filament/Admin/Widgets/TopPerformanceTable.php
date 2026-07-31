<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class TopPerformanceTable extends TableWidget
{
    protected static ?string $heading = '🏆 Top 10 Karyawan Terbaik';

    protected ?string $description = 'Peringkat 10 karyawan dengan skor evaluasi kinerja tertinggi periode ini';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    protected ?string $pollingInterval = '30s';

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
                    ->with([
                        'employee.department',
                        'employee.section',
                    ])
                    ->orderByDesc('final_score')
                    ->limit(10)
            )
            ->striped()
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('Peringkat')
                    ->rowIndex()
                    ->alignCenter()
                    ->width('90px')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(function ($state): string {
                        $rank = (int) $state;

                        return match ($rank) {
                            1 => '🥇 #1',
                            2 => '🥈 #2',
                            3 => '🥉 #3',
                            default => "#{$rank}",
                        };
                    }),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary')
                    ->description(function ($record): string {
                        $dept = $record->employee?->department?->name ?? 'Tanpa Departemen';
                        $section = $record->employee?->section?->name;

                        return $section ? "{$dept} • {$section}" : $dept;
                    }),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Skor Akhir')
                    ->numeric(decimalPlaces: 2)
                    ->alignCenter()
                    ->weight(FontWeight::ExtraBold)
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori Kinerja')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-')
                    ->color(fn (?string $state): string => match ((string) $state) {
                        'Outstanding' => 'success',
                        'Excellent'   => 'info',
                        'Good'        => 'warning',
                        'Fair'        => 'gray',
                        'Poor'        => 'danger',
                        default       => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ((string) $state) {
                        'Outstanding', 'Excellent' => 'heroicon-m-sparkles',
                        'Good'                     => 'heroicon-m-hand-thumb-up',
                        'Fair'                     => 'heroicon-m-minus-circle',
                        'Poor'                     => 'heroicon-m-exclamation-triangle',
                        default                    => 'heroicon-m-question-mark-circle',
                    }),
            ])
            ->emptyStateHeading('Belum Ada Peringkat Karyawan')
            ->emptyStateDescription("Belum ada data evaluasi kinerja yang disetujui untuk Semester {$semester} Tahun {$year}.")
            ->emptyStateIcon('heroicon-o-trophy');
    }
}