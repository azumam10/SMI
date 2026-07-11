<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveTypes\Pages;

use App\Filament\Admin\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateLeaveType extends CreateRecord
{
    protected static string $resource = LeaveTypeResource::class;
}
