<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class SectionPerformanceChart extends ChartWidget
{
    protected ?string $heading = 'Performa Rata-rata per Seksi';

    protected ?string $description = 'Rata-rata skor evaluasi kinerja karyawan berdasarkan seksi / unit kerja';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    // Filter default
    public ?string $filter = null;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole(['super_admin', 'hrd', 'kepala_bagian']);
    }

    /**
     * Menambahkan Filter Dropdown Periode (Tahun & Semester)
     */
    protected function getFilters(): ?array
    {
        $currentYear = now()->year;

        return [
            "{$currentYear}-1" => "Tahun {$currentYear} - Semester 1",
            "{$currentYear}-2" => "Tahun {$currentYear} - Semester 2",
            (($currentYear - 1) . '-2') => 'Tahun ' . ($currentYear - 1) . ' - Semester 2',
        ];
    }

    protected function getData(): array
    {
        // ── Parse filter atau default ke semester & tahun berjalan ──────
        if ($this->filter && str_contains($this->filter, '-')) {
            [$year, $semester] = explode('-', $this->filter);
            $year = (int) $year;
            $semester = (int) $semester;
        } else {
            $year = now()->year;
            $semester = now()->month <= 6 ? 1 : 2;
        }

        // ── Agregasi SQL Langsung di DB (Sangat Cepat & Efisien Memori) ──
        $sections = PerformanceReview::query()
            ->join('employees', 'performance_reviews.employee_id', '=', 'employees.id')
            ->join('sections', 'employees.section_id', '=', 'sections.id')
            ->where('performance_reviews.status', PerformanceReview::STATUS_APPROVED)
            ->where('performance_reviews.year', $year)
            ->where('performance_reviews.semester', $semester)
            ->select(
                'sections.name as section_name',
                DB::raw('ROUND(AVG(performance_reviews.final_score), 2) as avg_score')
            )
            ->groupBy('sections.id', 'sections.name')
            ->orderByDesc('avg_score')
            ->pluck('avg_score', 'section_name');

        if ($sections->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Rata-rata Nilai',
                        'data' => [0],
                        'backgroundColor' => '#94a3b8',
                    ],
                ],
                'labels' => ['Belum ada data evaluasi'],
            ];
        }

        // ── Palet Warna Harmonis dengan Fallback Modulo ────────────────
        $palette = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#f97316'];
        $backgroundColors = [];
        $i = 0;

        foreach ($sections as $score) {
            $backgroundColors[] = $palette[$i % count($palette)];
            $i++;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Rata-rata Skor (Skala 100)',
                    'data' => $sections->values()->toArray(),
                    'backgroundColor' => $backgroundColors,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $sections->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Opsi Konfigurasi Chart.js
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false, // Sembunyikan legenda karena tiap batang sudah berkategori
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'stepSize' => 20,
                    ],
                    'grid' => [
                        'drawBorder' => false,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false, // Hilangkan garis vertikal
                    ],
                ],
            ],
        ];
    }
}