<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

final class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Berdasarkan data real PT SMI — saat ini semua karyawan tercatat
        // di departemen Produksi. Departemen lain disiapkan untuk ekspansi.
        $departments = [
            ['code' => 'PROD',  'name' => 'Produksi'],
            ['code' => 'HRGA',  'name' => 'Human Resources & General Affairs'],
            ['code' => 'QC',    'name' => 'Quality Control'],
            ['code' => 'PPIC',  'name' => 'Production Planning & Inventory Control'],
            ['code' => 'ENG',   'name' => 'Engineering & Maintenance'],
            ['code' => 'FIN',   'name' => 'Finance & Accounting'],
            ['code' => 'PURC',  'name' => 'Purchasing'],
            ['code' => 'WH',    'name' => 'Warehouse & Logistik'],
            ['code' => 'IT',    'name' => 'Information Technology'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }
    }
}
