<?php

declare(strict_types=1);

namespace App\Support;

use Filament\Notifications\Notification;

final class DatabaseNotifier
{
    /**
     * Notifikasi saat melakukan Write ke Database Master
     */
    public static function write(string $title): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        $master = DatabaseConnection::write();

        Notification::make()
            ->title($title)
            ->body(
                "WRITE DATABASE\n\n".
                'Status : '.($master['online'] ? 'ONLINE' : 'OFFLINE')."\n".
                "Hostname : {$master['hostname']}\n".
                'Server ID : '.($master['server_id'] ?? '-')
            )
            ->success()
            ->duration(6000)
            ->send();
    }

    /**
     * Notifikasi opsional jika ingin memicu notifikasi Read Replica
     */
    public static function read(string $title = 'Data Dimuat dari Replica'): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            return;
        }

        $replica = DatabaseConnection::read();

        Notification::make()
            ->title($title)
            ->body(
                "READ DATABASE\n\n".
                'Status : '.($replica['online'] ? 'ONLINE' : 'OFFLINE')."\n".
                "Hostname : {$replica['hostname']}\n".
                'Server ID : '.($replica['server_id'] ?? '-')
            )
            ->info()
            ->duration(4000)
            ->send();
    }
}
