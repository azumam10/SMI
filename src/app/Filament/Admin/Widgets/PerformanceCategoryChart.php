<?php

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;

class PerformanceCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Kategori Penilaian'; // <-- hapus static

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        $categories = PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $year)
            ->where('semester', $semester)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $labels = ['Outstanding', 'Excellent', 'Good', 'Fair', 'Poor'];
        $colors = [
            'Outstanding' => '#22c55e',
            'Excellent'   => '#3b82f6',
            'Good'        => '#eab308',
            'Fair'        => '#6b7280',
            'Poor'        => '#ef4444',
        ];

        $data = array_map(fn ($label) => $categories[$label] ?? 0, $labels);
        $backgroundColors = array_map(fn ($label) => $colors[$label] ?? '#gray', $labels);

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}