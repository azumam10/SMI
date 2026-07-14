<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;

class SectionPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Performa per Seksi'; // <-- tanpa static

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        // Ambil data per seksi (section), bukan department
        $sections = PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $year)
            ->where('semester', $semester)
            ->with('employee.section')
            ->get()
            ->groupBy('employee.section.name')
            ->map(fn ($items) => round($items->avg('final_score'), 2))
            ->sortDesc();

        if ($sections->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Rata-rata Nilai',
                        'data' => [0],
                        'backgroundColor' => '#94a3b8',
                    ],
                ],
                'labels' => ['Belum ada data'],
            ];
        }

        $colors = ['#3b82f6', '#22c55e', '#eab308', '#f97316', '#ef4444', '#8b5cf6', '#ec4899'];

        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Nilai',
                    'data' => $sections->values()->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $sections->count()),
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $sections->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd', 'kepala_bagian']);
    }
}