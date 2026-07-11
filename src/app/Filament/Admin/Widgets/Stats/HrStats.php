<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets\Stats;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class HrStats extends StatsOverviewWidget
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
                'Cuti Pending',
                LeaveRequest::where('status', 'pending')->count()
            )
                ->description('Menunggu approval')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(
                'Cuti Disetujui',
                LeaveRequest::where('status', 'hrd_approved')->count()
            )
                ->description('Sudah disetujui HRD')
                ->icon('heroicon-o-check-badge')
                ->color('success'),

            Stat::make(
                'Departemen',
                Department::count()
            )
                ->description('Departemen aktif')
                ->icon('heroicon-o-building-office')
                ->color('info'),

        ];
    }
}
