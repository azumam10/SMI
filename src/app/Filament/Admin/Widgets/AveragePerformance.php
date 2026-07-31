<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class AveragePerformance extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $currentMonth = now()->month;
        $year = now()->year;
        $semester = $currentMonth <= 6 ? 1 : 2;

        // ── 1. Hitung Semester Sebelumnya ─────────────────────────────
        $prevSemester = $semester === 1 ? 2 : 1;
        $prevYear = $semester === 1 ? $year - 1 : $year;

        // ── 2. Perolehan Nilai Rata-rata ──────────────────────────────
        $currentAvg = (float) (PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $year)
            ->where('semester', $semester)
            ->avg('final_score') ?? 0);

        $prevAvg = (float) (PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $prevYear)
            ->where('semester', $prevSemester)
            ->avg('final_score') ?? 0);

        // ── 3. Hitung Selisih Poin (Trend) ────────────────────────────
        $diff = round($currentAvg - $prevAvg, 2);
        $hasPrevData = $prevAvg > 0;

        if (! $hasPrevData) {
            $description = "Semester {$semester} / {$year}";
            $descriptionIcon = 'heroicon-m-academic-cap';
            $color = 'primary';
        } elseif ($diff >= 0) {
            $description = "+{$diff} poin dari Smst {$prevSemester}/{$prevYear}";
            $descriptionIcon = 'heroicon-m-arrow-trending-up';
            $color = 'success';
        } else {
            $description = "{$diff} poin dari Smst {$prevSemester}/{$prevYear}";
            $descriptionIcon = 'heroicon-m-arrow-trending-down';
            $color = 'danger';
        }

        $chartData = $hasPrevData
            ? [$prevAvg, round(($prevAvg + $currentAvg) / 2, 2), $currentAvg]
            : [0, 0, $currentAvg];

        return [
            Stat::make(
                'Rata-rata Nilai Perusahaan',
                number_format($currentAvg, 2)
            )
                ->description($description)
                ->descriptionIcon($descriptionIcon)
                ->color($color)
                ->icon('heroicon-o-chart-bar-square')
                ->chart($chartData)
                ->extraAttributes([
                    'class' => 'cursor-pointer transition-all duration-300 hover:shadow-md hover:ring-1 hover:ring-primary-500/50',
                ]),
        ];
    }
}
