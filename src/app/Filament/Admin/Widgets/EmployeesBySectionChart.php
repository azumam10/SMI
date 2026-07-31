<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Section;
use Filament\Widgets\ChartWidget;

final class EmployeesBySectionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Karyawan per Section';

    protected ?string $description = 'Jumlah total karyawan aktif di setiap divisi/section';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        // ── 1. Ambil Data & Urutkan Terbanyak ke Terkecil ──────────────
        $sections = Section::query()
            ->withCount(['employees' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->sortByDesc('employees_count');

        // Palette warna modern (Tailwind Colors)
        $colors = [
            'rgba(59, 130, 246, 0.85)',  // Blue
            'rgba(16, 185, 129, 0.85)',  // Emerald
            'rgba(245, 158, 11, 0.85)',  // Amber
            'rgba(139, 92, 246, 0.85)',  // Purple
            'rgba(236, 72, 153, 0.85)',  // Pink
            'rgba(14, 165, 233, 0.85)',  // Sky
            'rgba(20, 184, 166, 0.85)',  // Teal
            'rgba(249, 115, 22, 0.85)',  // Orange
            'rgba(99, 102, 241, 0.85)',  // Indigo
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Karyawan',
                    'data' => $sections->pluck('employees_count')->values(),
                    'backgroundColor' => array_slice(
                        array_merge($colors, $colors),
                        0,
                        $sections->count()
                    ),
                    'borderRadius' => 8, // Bikin sudut batang melengkung (modern)
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $sections->pluck('name')->values(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Konfigurasi Tampilan Chart.js
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false, // Sembunyikan legend karena data hanya 1 kategori
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // Paksa sumbu Y hanya menampilkan angka bulat
                    ],
                    'grid' => [
                        'display' => true,
                        'drawBorder' => false,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false, // Sembunyikan garis grid vertikal agar lebih bersih
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
