<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Support\DatabaseConnection;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

final class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function afterSave(): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        $master = DatabaseConnection::write();

        Notification::make()
            ->title('Data berhasil diperbarui')
            ->body(
                "WRITE DATABASE\n\n".
                "Server : {$master['label']}\n".
                "Hostname : {$master['hostname']}"
            )
            ->success()
            ->duration(7000)
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [

            ViewAction::make(),

            DeleteAction::make()
                ->after(function () {

                    if (! auth()->user()?->hasRole('super_admin')) {
                        return;
                    }

                    $master = DatabaseConnection::write();

                    Notification::make()
                        ->title('Data berhasil dihapus')
                        ->body(
                            "WRITE DATABASE\n\n".
                            "Server : {$master['label']}\n".
                            "Hostname : {$master['hostname']}"
                        )
                        ->danger()
                        ->duration(7000)
                        ->send();

                }),

            ForceDeleteAction::make(),

            RestoreAction::make(),

        ];
    }
}
