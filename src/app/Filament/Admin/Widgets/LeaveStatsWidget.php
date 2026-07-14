<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class LeaveStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s'; // <-- hapus static

    protected function getStats(): array
    {
        $year = now()->year;

        // ── 1. Menunggu Approval ──────────────────────────────────
        $pending = LeaveRequest::pending()->count();

        // ── 2. Sedang Cuti Hari Ini ──────────────────────────────
        $onLeaveToday = LeaveRequest::query()
            ->where('status', 'hrd_approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->count();

        // ── 3. Total Hari Cuti Disetujui (Tahun Ini) ─────────────
        $totalApprovedDays = (float) LeaveRequest::query() // <-- cast ke float
            ->where('status', 'hrd_approved')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        // ── 4. Rata-rata Hari Cuti per Karyawan (Tahun Ini) ─────
        $totalEmployees = Employee::where('is_active', true)->count();
        $avgLeavePerEmployee = $totalEmployees > 0
            ? round($totalApprovedDays / $totalEmployees, 2)
            : 0;

        // ── 5. Sisa Kuota Cuti (asumsi 12 hari/tahun) ───────────
        $quotaPerYear = 12; // bisa dari setting
        $remainingQuota = max(0, $quotaPerYear - $avgLeavePerEmployee);

        return [
            Stat::make('Menunggu Approval', $pending)
                ->description('Perlu diproses HRD')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->extraAttributes(['class' => 'cursor-pointer']),

            Stat::make('Sedang Cuti', $onLeaveToday)
                ->description('Karyawan cuti hari ini')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

            Stat::make('Total Cuti Terpakai', number_format($totalApprovedDays, 1) . ' Hari')
                ->description("Seluruh karyawan tahun {$year}")
                ->icon('heroicon-o-chart-bar')
                ->color('info'),

            Stat::make('Rata-rata Cuti', number_format($avgLeavePerEmployee, 1) . ' Hari')
                ->description("Per karyawan aktif tahun {$year}")
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Sisa Kuota', number_format($remainingQuota, 1) . ' Hari')
                ->description("Rata-rata sisa cuti per karyawan")
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd']);
    }
}