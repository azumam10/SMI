<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class LeaveMonthlyChart extends ChartWidget
{
    protected ?string $heading = 'Statistik Pengajuan Cuti Bulanan';

    protected ?string $description = 'Perbandingan total pengajuan dan cuti yang disetujui per bulan';

    
    protected ?string $maxHeight = '300px';

    protected static ?int $sort = 2;

    // Filter default ke tahun berjalan
    public ?string $filter = null;

    /**
     * Menambahkan Dropdown Filter Tahun di Pojok Kanan Widget
     */
    protected function getFilters(): ?array
    {
        $currentYear = now()->year;

        return [
            (string) $currentYear => "Tahun {$currentYear}",
            (string) ($currentYear - 1) => 'Tahun ' . ($currentYear - 1),
        ];
    }

    protected function getData(): array
    {
        $activeYear = (int) ($this->filter ?? now()->year);

        // ── 1. Total Seluruh Pengajuan Cuti ────────────────────────────
        $totalRequests = LeaveRequest::query()
            ->selectRaw('MONTH(start_date) as month, COUNT(*) as total')
            ->whereYear('start_date', $activeYear)
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->pluck('total', 'month');

        // ── 2. Total Cuti yang Disetujui (HRD Approved) ─────────────────
        $approvedRequests = LeaveRequest::query()
            ->selectRaw('MONTH(start_date) as month, COUNT(*) as total')
            ->where('status', 'hrd_approved')
            ->whereYear('start_date', $activeYear)
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->pluck('total', 'month');

        $dataTotal = [];
        $dataApproved = [];

        for ($i = 1; $i <= 12; $i++) {
            $dataTotal[] = $totalRequests[$i] ?? 0;
            $dataApproved[] = $approvedRequests[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Pengajuan',
                    'data' => $dataTotal,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.85)', // Amber
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Disetujui (Approved)',
                    'data' => $dataApproved,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.85)', // Emerald
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                ],
            ],

            'labels' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Opsi Konfigurasi Chart.js untuk Tampilan Profesional
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0, // Hanya angka bulat
                    ],
                    'grid' => [
                        'drawBorder' => false,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false, // Sembunyikan garis vertikal
                    ],
                ],
            ],
        ];
    }
}