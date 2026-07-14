<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class EmployeeLeaveQuotaWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '60s'; // <-- tanpa static

    protected function getStats(): array
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return [];
        }

        $year = now()->year;
        $quotaPerYear = 12;

        $usedLeave = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'hrd_approved')
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $remaining = max(0, $quotaPerYear - $usedLeave);

        $pending = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->pending()
            ->count();

        return [
            Stat::make('Kuota Cuti', "{$quotaPerYear} Hari")
                ->description('Total kuota tahun ini')
                ->icon('heroicon-o-calendar')
                ->color('gray'),

            Stat::make('Cuti Terpakai', number_format($usedLeave, 1) . ' Hari')
                ->description("Tahun {$year}")
                ->icon('heroicon-o-arrow-trending-up')
                ->color('warning'),

            Stat::make('Sisa Kuota', number_format($remaining, 1) . ' Hari')
                ->description('Masih tersedia')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make('Menunggu Approval', $pending)
                ->description('Pengajuan cuti aktif')
                ->icon('heroicon-o-clock')
                ->color('info'),
        ];
    }

    // public static function canView(): bool
    // {
    //     return auth()->user()->hasAnyRole(['karyawan', 'kepala_bagian']);
    // }
}