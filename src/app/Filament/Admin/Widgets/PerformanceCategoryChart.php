<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;

final class PerformanceCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Kategori Penilaian';

    protected ?string $description = 'Proporsi sebaran hasil evaluasi kinerja karyawan berdasarkan kategori';

    // Batasi tinggi chart agar tidak terlalu besar/melar secara vertikal
    protected ?string $maxHeight = '300px';

    protected static ?int $sort = 3;

    // Filter default
    public ?string $filter = null;

    /**
     * Menambahkan Filter Dropdown Periode (Tahun & Semester)
     */
    protected function getFilters(): ?array
    {
        $currentYear = now()->year;

        return [
            "{$currentYear}-1" => "Tahun {$currentYear} - Semester 1",
            "{$currentYear}-2" => "Tahun {$currentYear} - Semester 2",
            (($currentYear - 1).'-2') => 'Tahun '.($currentYear - 1).' - Semester 2',
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

        // ── Query total per kategori ─────────────────────────────────────
        $categories = PerformanceReview::query()
            ->where('status', PerformanceReview::STATUS_APPROVED)
            ->where('year', $year)
            ->where('semester', $semester)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $labels = ['Outstanding', 'Excellent', 'Good', 'Fair', 'Poor'];

        // Warna Hex Standar Tailwind untuk masing-masing Kategori
        $colors = [
            'Outstanding' => '#10b981', // Emerald
            'Excellent'   => '#3b82f6', // Blue
            'Good'        => '#f59e0b', // Amber
            'Fair'        => '#6b7280', // Gray
            'Poor'        => '#ef4444', // Red
        ];

        $data = array_map(fn ($label) => (int) ($categories[$label] ?? 0), $labels);
        
        // Perbaikan: Fallback warna menggunakan hex valid '#9ca3af', bukan '#gray'
        $backgroundColors = array_map(fn ($label) => $colors[$label] ?? '#9ca3af', $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Karyawan',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 2,
                    'hoverOffset' => 6, // Efek menonjol saat kursor diarahkan ke slice chart
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * Opsi Konfigurasi Chart.js untuk Tampilan Modern
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '70%', // Efek ring doughnut modern & tipis
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true, // Mengubah ikon legenda dari persegi menjadi lingkaran kecil
                        'padding' => 15,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
{
    return auth()->user()?->hasAnyRole([
        'super_admin',
        'hrd',
    ]) ?? false;
}
}