<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class LeaveMonthlyChart extends ChartWidget
{
    protected ?string $heading = 'Statistik Pengajuan Cuti';

    protected ?string $maxHeight = '320px';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $year = now()->year;

        $rows = LeaveRequest::query()
            ->selectRaw('MONTH(start_date) as month')
            ->selectRaw('COUNT(*) as total')
            ->whereYear('start_date', $year)
            ->groupBy(DB::raw('MONTH(start_date)'))
            ->pluck('total', 'month');

        $data = [];

        for ($i = 1; $i <= 12; $i++) {
            $data[] = $rows[$i] ?? 0;
        }

        return [

            'datasets' => [
                [
                    'label' => 'Pengajuan',
                    'data' => $data,
                ],
            ],

            'labels' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'Mei',
                'Jun',
                'Jul',
                'Agu',
                'Sep',
                'Okt',
                'Nov',
                'Des',
            ],

        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
