<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Section;
use Illuminate\Database\Seeder;

final class SectionSeeder extends Seeder
{
    public function run(): void
    {
        // Bagian/Seksi diambil langsung dari kolom 'Bagian' di data Excel PT SMI.
        // Semua bagian saat ini berada di bawah departemen Produksi.
        $prod = Department::where('code', 'PROD')->first();

        if (! $prod) {
            return;
        }

        $sections = [
            // ── Struktural ────────────────────────────────────────────
            ['code' => 'DIR-UTAMA', 'name' => 'Direktur Utama'],
            ['code' => 'MGR-PROD',  'name' => 'Manager Produksi'],
            ['code' => 'KB',        'name' => 'Kepala Bagian'],
            ['code' => 'STAFF',     'name' => 'Staff'],

            // ── Bagian Produksi (dari data Excel) ─────────────────────
            ['code' => 'P1-SEW',    'name' => 'P1 - Sewing & QC Sewing'],
            ['code' => 'P2-MTR',    'name' => 'P2 - Meter, Setting & Naiki'],
            ['code' => 'P3-LAT',    'name' => 'P3 - Latex'],
            ['code' => 'P4-MCH',    'name' => 'P4 - Machining'],
            ['code' => 'P5-PREP',   'name' => 'P5 - Prep Sponge'],
            ['code' => 'P6-DSP',    'name' => 'P6 - Dispo Cuff & ID Band'],

            // ── Bagian Pendukung (dari data Excel) ────────────────────
            ['code' => 'MNT',       'name' => 'Maintenance'],
            ['code' => 'ENG',       'name' => 'Engineering'],
            ['code' => 'GDG',       'name' => 'Gudang, IQC & OQC'],
            ['code' => 'SECURITY',  'name' => 'Security'],
            ['code' => 'DRIVER',    'name' => 'Driver'],
        ];

        foreach ($sections as $sec) {
            Section::firstOrCreate(
                ['code' => $sec['code']],
                array_merge($sec, ['department_id' => $prod->id])
            );
        }
    }
}
