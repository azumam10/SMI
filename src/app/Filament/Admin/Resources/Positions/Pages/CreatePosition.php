<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Positions\Pages;

use App\Filament\Admin\Resources\Positions\PositionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreatePosition extends CreateRecord
{
    protected static string $resource = PositionResource::class;
}
