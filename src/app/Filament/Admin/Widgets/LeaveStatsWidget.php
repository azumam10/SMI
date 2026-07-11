<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class LeaveStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            Stat::make(
                'Total Karyawan',
                Employee::where('is_active', true)->count()
            )
                ->description('Karyawan aktif')
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make(
                'Menunggu Approval',
                LeaveRequest::pending()->count()
            )
                ->description('Belum diproses')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(
                'Sedang Cuti',
                LeaveRequest::query()
                    ->where('status', 'hrd_approved')
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today())
                    ->count()
            )
                ->description('Hari ini')
                ->icon('heroicon-o-calendar-days')
                ->color('success'),

                Stat::make(
                    'Hari Cuti Tahun Ini',
                    LeaveRequest::query()
                    ->where('status', 'hrd_approved')
                    ->whereYear('start_date', now()->year)
                    ->sum('total_days') . ' Hari'
                    )
                    ->description('Tahun ' . now()->year)
                    ->icon('heroicon-o-chart-bar')
                    ->color('info'),

        ];
    }
}
