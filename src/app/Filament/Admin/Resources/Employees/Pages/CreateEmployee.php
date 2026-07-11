<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
