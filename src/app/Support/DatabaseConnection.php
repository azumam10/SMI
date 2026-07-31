<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use PDO;
use Throwable;

final class DatabaseConnection
{
    public static function forceMaster(Builder $query): Builder
    {
        return $query->useWritePdo();
    }

    public static function read(): array
    {
        try {

            $row = DB::connection()
                ->getReadPdo()
                ->query('SELECT @@server_id server_id, @@hostname hostname')
                ->fetch(PDO::FETCH_ASSOC);

            return [
                'online' => true,
                'server_id' => (int) $row['server_id'],
                'hostname' => $row['hostname'],
                'label' => 'REPLICA',
            ];

        } catch (Throwable) {

            return [
                'online' => false,
                'server_id' => null,
                'hostname' => '-',
                'label' => 'OFFLINE',
            ];

        }
    }

    public static function write(): array
    {
        try {

            $row = DB::connection()
                ->getPdo()
                ->query('SELECT @@server_id server_id, @@hostname hostname')
                ->fetch(PDO::FETCH_ASSOC);

            return [
                'online' => true,
                'server_id' => (int) $row['server_id'],
                'hostname' => $row['hostname'],
                'label' => 'MASTER',
            ];

        } catch (Throwable) {

            return [
                'online' => false,
                'server_id' => null,
                'hostname' => '-',
                'label' => 'OFFLINE',
            ];

        }
    }

    public static function replication(): array
    {
        try {

            $status = DB::connection()
                ->getReadPdo()
                ->query('SHOW REPLICA STATUS')
                ->fetch(PDO::FETCH_ASSOC);

            return [

                'running' => ($status['Replica_IO_Running'] ?? '') === 'Yes'
                    &&
                    ($status['Replica_SQL_Running'] ?? '') === 'Yes',

                'delay' => $status['Seconds_Behind_Source'] ?? 0,

            ];

        } catch (Throwable) {

            return [

                'running' => false,

                'delay' => null,

            ];

        }
    }
}
