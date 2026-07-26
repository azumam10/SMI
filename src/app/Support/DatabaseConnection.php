<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseConnection
{
    /**
     * Paksa query menggunakan database master.
     */
    public static function forceMaster(Builder $query): Builder
    {
        return $query->useWritePdo();
    }

    /**
     * Mendeteksi database yang BENAR-BENAR dipakai.
     */
    public static function current(): array
    {
        try {

            $pdo = DB::connection()->getReadPdo();

            $stmt = $pdo->query("
                SELECT
                    @@server_id AS server_id,
                    @@hostname  AS hostname
            ");

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $serverId = (int) $row['server_id'];

            return [

                'server_id' => $serverId,

                'hostname' => $row['hostname'],

                'is_master' => $serverId === 1,

                'label' => $serverId === 1
                    ? 'MASTER'
                    : 'REPLICA',

                'color' => $serverId === 1
                    ? 'warning'
                    : 'success',

            ];

        } catch (\Throwable) {

            return [

                'server_id' => null,

                'hostname' => '-',

                'is_master' => false,

                'label' => 'UNKNOWN',

                'color' => 'gray',

            ];
        }
    }
}