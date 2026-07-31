<?php

declare(strict_types=1);

namespace App\Filament\Admin\Traits;

use App\Filament\Admin\Widgets\DatabaseMonitor;

trait HasDatabaseIndicator
{
    protected function getHeaderWidgets(): array
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return [];
        }

        return [
            DatabaseMonitor::class,
        ];
    }
}
