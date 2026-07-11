<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Section;
use Filament\Widgets\ChartWidget;

class EmployeesBySectionChart extends ChartWidget
{
    protected ?string $heading = 'Distribusi Karyawan per Section';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $sections = Section::withCount([
            'employees' => fn ($q) => $q->where('is_active', true),
        ])->get();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Karyawan',
                    'data' => $sections->pluck('employees_count'),
                ],
            ],

            'labels' => $sections->pluck('name'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}