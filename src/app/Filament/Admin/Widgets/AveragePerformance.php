<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AveragePerformance extends BaseWidget
{
    protected function getStats(): array
    {
        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        $average = PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $year)
            ->where('semester', $semester)
            ->avg('final_score');

        return [
            Stat::make(
                'Rata-rata Nilai Perusahaan',
                number_format($average ?? 0, 2)
            )
            ->description("Semester {$semester} / {$year}")
            ->descriptionIcon('heroicon-o-chart-bar')
            ->color('success')
            ->extraAttributes(['class' => 'cursor-pointer']),
        ];
    }
}