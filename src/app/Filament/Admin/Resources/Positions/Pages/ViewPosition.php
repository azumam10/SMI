<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Positions\Pages;

use App\Filament\Admin\Resources\Positions\PositionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPosition extends ViewRecord
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
