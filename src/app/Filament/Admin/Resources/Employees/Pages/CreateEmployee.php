<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Support\DatabaseConnection;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterCreate(): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        $master = DatabaseConnection::write();

        Notification::make()
            ->title('Data berhasil ditambahkan')
            ->body(
                "WRITE DATABASE\n\n".
                "Server : {$master['label']}\n".
                "Hostname : {$master['hostname']}"
            )
            ->success()
            ->duration(7000)
            ->send();
    }
}
