<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Filament\Admin\Traits\HasDatabaseIndicator;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListEmployees extends ListRecords
{
    use HasDatabaseIndicator;

    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
