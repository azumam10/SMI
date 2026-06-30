<?php

use Illuminate\Support\Str;

return [
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        'sqlite' => [
            'driver'                  => 'sqlite',
            'url'                     => env('DB_URL'),
            'database'                => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'                  => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        // ── MySQL Master-Replica (read/write splitting) ───────────────
        //
        // 'write' → umamis_db_master : INSERT, UPDATE, DELETE, DDL
        // 'read'  → umamis_db_replica: SELECT (dashboard, laporan, KPI)
        // 'sticky'= true: jika sudah write di request ini, baca dari
        //           master supaya tidak kena replication lag
        // ─────────────────────────────────────────────────────────────
        'mysql' => [
            'driver' => 'mysql',
            'url'    => env('DB_URL'),

            'write' => [
                'host'     => env('DB_HOST', 'umamis_db_master'),
                'port'     => env('DB_PORT', '3306'),
                'username' => env('DB_USERNAME', 'djambred'),
                'password' => env('DB_PASSWORD', ''),
            ],

            'read' => [
                'host'     => env('DB_REPLICA_HOST', 'umamis_db_replica'),
                'port'     => env('DB_REPLICA_PORT', '3306'),
                'username' => env('DB_USERNAME', 'djambred'),
                'password' => env('DB_PASSWORD', ''),
            ],

            'sticky'    => true,
            'database'  => env('DB_DATABASE', 'umamis_hris'),
            'prefix'    => '',
            'prefix_indexes' => true,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict'    => true,
            'engine'    => 'InnoDB',
        ],

        // ── Koneksi eksplisit ke replica ──────────────────────────────
        // Untuk query laporan berat / analytics, pakai:
        //   Employee::onReplica()->get();
        //   DB::connection('mysql_replica')->table(...)->get();
        // ─────────────────────────────────────────────────────────────
        'mysql_replica' => [
            'driver'    => 'mysql',
            'host'      => env('DB_REPLICA_HOST', 'umamis_db_replica'),
            'port'      => env('DB_REPLICA_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'umamis_hris'),
            'username'  => env('DB_USERNAME', 'djambred'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict'    => true,
            'engine'    => 'InnoDB',
        ],

        'pgsql' => [
            'driver'         => 'pgsql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '5432'),
            'database'       => env('DB_DATABASE', 'laravel'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
            'search_path'    => 'public',
            'sslmode'        => 'prefer',
        ],

        'sqlsrv' => [
            'driver'         => 'sqlsrv',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', 'localhost'),
            'port'           => env('DB_PORT', '1433'),
            'database'       => env('DB_DATABASE', 'laravel'),
            'username'       => env('DB_USERNAME', 'root'),
            'password'       => env('DB_PASSWORD', ''),
            'charset'        => 'utf8',
            'prefix'         => '',
            'prefix_indexes' => true,
        ],
    ],

    'migrations' => [
        'table'                  => 'migrations',
        'update_date_on_publish' => true,
    ],

    'redis' => [
        'client'  => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_') . '_database_'),
        ],
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', 'umamis_redis'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', 'umamis_redis'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],
];