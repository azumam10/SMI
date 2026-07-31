<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Support\DatabaseConnection;
use Filament\Widgets\Widget;

final class DatabaseMonitor extends Widget
{
    protected string $view = 'filament.admin.widgets.database-monitor';

    protected int|string|array $columnSpan = 'full';

    public function getData(): array
    {
        return [

            'master' => DatabaseConnection::write(),

            'replica' => DatabaseConnection::read(),

            'replication' => DatabaseConnection::replication(),

        ];
    }
}
