<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class LeaveStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd']);
    }

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
        $totalApprovedDays = (float) LeaveRequest::query()
            ->where('status', 'hrd_approved')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        // ── 4. Rata-rata Hari Cuti per Karyawan (Tahun Ini) ─────
        $totalEmployees = Employee::where('is_active', true)->count();
        $avgLeavePerEmployee = $totalEmployees > 0
            ? round($totalApprovedDays / $totalEmployees, 2)
            : 0;

        // ── 5. Sisa Kuota Cuti (asumsi 12 hari/tahun) ───────────
        $quotaPerYear = 12;
        $remainingQuota = max(0, $quotaPerYear - $avgLeavePerEmployee);

        return [
            Stat::make('Menunggu Approval', $pending)
                ->description($pending > 0 ? 'Perlu segera diproses HRD' : 'Tidak ada antrean cuti')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-clock')
                ->color($pending > 0 ? 'warning' : 'success')
                ->chart([0, 2, $pending + 1, $pending]) // Menampilkan grafik mini/sparkline
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:ring-2 hover:ring-warning-500 transition-all duration-300',
                ]),

            Stat::make('Sedang Cuti', $onLeaveToday)
                ->description('Karyawan yang libur hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->chart([$onLeaveToday > 0 ? 1 : 0, $onLeaveToday + 2, $onLeaveToday]), // Efek grafik fluktuatif

            Stat::make('Total Cuti Terpakai', number_format($totalApprovedDays, 1).' Hari')
                ->description("Seluruh karyawan di tahun {$year}")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-chart-bar')
                ->color('primary')
                ->chart([0, $totalApprovedDays * 0.3, $totalApprovedDays * 0.7, $totalApprovedDays]), // Grafik menanjak

            Stat::make('Rata-rata Cuti', number_format($avgLeavePerEmployee, 1).' Hari')
                ->description("Per karyawan aktif tahun {$year}")
                ->icon('heroicon-o-user-group')
                ->color('gray'),

            Stat::make('Sisa Kuota', number_format($remainingQuota, 1).' Hari')
                ->description('Rata-rata sisa cuti per karyawan')
                ->descriptionIcon($remainingQuota <= 3 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-shield-check')
                ->icon('heroicon-o-check-badge')
                ->color($remainingQuota <= 3 ? 'danger' : 'success') // Jika sisa kuota menipis, warna jadi merah
                ->chart([$quotaPerYear, $quotaPerYear * 0.7, $remainingQuota + 2, $remainingQuota]), // Grafik menurun
        ];
    }

    
}
