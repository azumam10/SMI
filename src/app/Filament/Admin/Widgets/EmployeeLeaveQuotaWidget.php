<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class EmployeeLeaveQuotaWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user?->id)->first();

        if (! $employee) {
            return [];
        }

        $year = now()->year;
        $quotaPerYear = 12.0; // Cast ke float

        // Force cast ke (float) dari hasil query DB
        $usedLeave = (float) (LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'hrd_approved')
            ->whereYear('start_date', $year)
            ->sum('total_days') ?? 0);

        $remaining = max(0.0, $quotaPerYear - $usedLeave);

        $pending = (int) LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->pending()
            ->count();

        // Indikator Warna & Ikon Dinamis
        $remainingColor = match (true) {
            $remaining <= 2.0 => 'danger',
            $remaining <= 5.0 => 'warning',
            default => 'success',
        };

        $remainingIcon = match (true) {
            $remaining <= 2.0 => 'heroicon-m-exclamation-triangle',
            $remaining <= 5.0 => 'heroicon-m-exclamation-circle',
            default => 'heroicon-m-check-badge',
        };

        return [
            Stat::make('Kuota Cuti', "{$quotaPerYear} Hari")
                ->description("Hak cuti tahun {$year}")
                ->descriptionIcon('heroicon-m-calendar')
                ->icon('heroicon-o-calendar-days')
                ->color('primary')
                ->chart([$quotaPerYear, $quotaPerYear, $quotaPerYear]),

            // Pastikan menggunakan (float) secara langsung di number_format
            Stat::make('Cuti Terpakai', number_format((float) $usedLeave, 1).' Hari')
                ->description('Disetujui HRD tahun ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-chart-bar')
                ->color('warning')
                ->chart([0, $usedLeave * 0.5, $usedLeave]),

            // Pastikan menggunakan (float) secara langsung di number_format
            Stat::make('Sisa Kuota', number_format((float) $remaining, 1).' Hari')
                ->description($remaining > 0 ? 'Tersedia untuk diajukan' : 'Kuota tahun ini habis')
                ->descriptionIcon($remainingIcon)
                ->icon('heroicon-o-check-badge')
                ->color($remainingColor)
                ->chart([$quotaPerYear, $quotaPerYear * 0.6, $remaining]),

            Stat::make('Menunggu Approval', $pending)
                ->description($pending > 0 ? 'Dalam proses pengajuan' : 'Tidak ada antrean cuti')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-clock' : 'heroicon-m-check-circle')
                ->icon('heroicon-o-clock')
                ->color($pending > 0 ? 'info' : 'gray')
                ->chart([0, $pending]),
        ];
    }
}
