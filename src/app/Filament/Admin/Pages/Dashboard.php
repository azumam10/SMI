<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ContractEndingSoon;
use App\Filament\Admin\Widgets\EmployeesOnLeaveToday;
use App\Filament\Admin\Widgets\LatestAccessLogs;
use App\Filament\Admin\Widgets\LeaveMonthlyChart;
use App\Filament\Admin\Widgets\LeaveStatsWidget;
use App\Filament\Admin\Widgets\EmployeesBySectionChart;
use App\Filament\Admin\Widgets\RecentLeaveRequests;
use App\Filament\Admin\Widgets\SectionEmployeeTable;
use App\Filament\Admin\Widgets\AveragePerformance;
use App\Filament\Admin\Widgets\PerformanceCategoryChart;
use App\Filament\Admin\Widgets\TopPerformanceTable;
use App\Filament\Admin\Widgets\BottomPerformanceTable;
use App\Filament\Admin\Widgets\SectionPerformanceChart;
use App\Filament\Admin\Widgets\PendingApprovalWidget;
use App\Filament\Admin\Widgets\MyTeamPerformanceWidget;
use App\Filament\Admin\Widgets\EmployeeLeaveQuotaWidget;
use App\Filament\Admin\Widgets\LowLeaveQuotaWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use UnitEnum;

final class Dashboard extends BaseDashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static bool $shouldRegisterNavigation = false;

    public function getWidgets(): array
    {
        return [

            LeaveStatsWidget::class,
            LeaveMonthlyChart::class,
            RecentLeaveRequests::class,
            EmployeesOnLeaveToday::class,
            ContractEndingSoon::class,
            EmployeesBySectionChart::class,
            SectionEmployeeTable::class,
            LatestAccessLogs::class,
            AveragePerformance::class,
            PerformanceCategoryChart::class,
            TopPerformanceTable::class,
            BottomPerformanceTable::class,
            SectionPerformanceChart::class,
            MyTeamPerformanceWidget::class,
            PendingApprovalWidget::class,
            EmployeeLeaveQuotaWidget::class,
            LowLeaveQuotaWidget::class,

        ];
    }
}
