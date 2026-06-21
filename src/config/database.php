<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection
    |--------------------------------------------------------------------------
    */
    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [

        /*
        |----------------------------------------------------------------------
        | SQLite (untuk testing/CI saja)
        |----------------------------------------------------------------------
        */
        'sqlite' => [
            'driver'   => 'sqlite',
            'url'      => env('DB_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix'   => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        /*
        |----------------------------------------------------------------------
        | MySQL dengan Read/Write Splitting (Master-Replica)
        |
        | 'write' → db-master  : INSERT, UPDATE, DELETE, DDL
        | 'read'  → db-replica : SELECT (termasuk dashboard, laporan, KPI)
        |
        | 'sticky' = true: setelah write dalam request yang sama,
        |   Laravel otomatis baca dari master (hindari replication lag).
        |----------------------------------------------------------------------
        */
        'mysql' => [
            'driver'    => 'mysql',
            'url'       => env('DB_URL'),

            // ── WRITE: selalu ke Master ────────────────────────────────────
            'write' => [
                'host'     => env('DB_HOST', 'umamis_db_master'),
                'port'     => env('DB_PORT', '3306'),
                'username' => env('DB_USERNAME', 'sankei'),
                'password' => env('DB_PASSWORD', 'secret'),
            ],

            // ── READ: selalu ke Replica ────────────────────────────────────
            'read' => [
                'host'     => env('DB_REPLICA_HOST', 'umamis_db_replica'),
                'port'     => env('DB_REPLICA_PORT', '3306'),
                'username' => env('DB_USERNAME', 'sankei'),
                'password' => env('DB_PASSWORD', 'secret'),
            ],

            // Paksa baca dari master jika sudah write di request yang sama
            'sticky'    => true,

            'database'  => env('DB_DATABASE', 'smart_hris'),
            'prefix'    => '',
            'prefix_indexes' => true,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict'    => true,
            'engine'    => 'InnoDB',
            'options'   => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        /*
        |----------------------------------------------------------------------
        | MySQL Replica — koneksi eksplisit
        |
        | Digunakan saat kita ingin PAKSA query ke replica,
        | misalnya untuk laporan berat, export data, analytics.
        |
        | Penggunaan di kode:
        |   DB::connection('mysql_replica')->table('employees')->get();
        |   Employee::on('mysql_replica')->where(...)->get();
        |----------------------------------------------------------------------
        */
        'mysql_replica' => [
            'driver'    => 'mysql',
            'url'       => env('DB_URL'),
            'host'      => env('DB_REPLICA_HOST', 'db-replica'),
            'port'      => env('DB_REPLICA_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'smart_hris'),
            'username'  => env('DB_USERNAME', 'sankei'),
            'password'  => env('DB_PASSWORD', 'secret'),
            'prefix'    => '',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'strict'    => true,
            'engine'    => 'InnoDB',
        ],

        /*
        |----------------------------------------------------------------------
        | PostgreSQL (cadangan, tidak digunakan)
        |----------------------------------------------------------------------
        */
        'pgsql' => [
            'driver'   => 'pgsql',
            'url'      => env('DB_URL'),
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'laravel'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8',
            'prefix'   => '',
            'prefix_indexes'  => true,
            'search_path'     => 'public',
            'sslmode'         => 'prefer',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */
    'migrations' => [
        'table'             => 'migrations',
        'update_date_on_publish' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis (cache dashboard + queue jobs)
    |--------------------------------------------------------------------------
    */
    'redis' => [
        'client'  => env('REDIS_CLIENT', 'phpredis'),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],
        'default' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', 'redis'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],
        'cache' => [
            'url'      => env('REDIS_URL'),
            'host'     => env('REDIS_HOST', 'redis'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port'     => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],
    ],

];