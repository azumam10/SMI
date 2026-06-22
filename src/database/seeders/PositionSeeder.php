<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        // Posisi & Level diambil dari data real Excel PT SMI.
        // Kolom 'Level' di Excel (ACC, HRGA, QA, dll) = jabatan fungsional.
        $positions = [
            // ── Struktural ────────────────────────────────────────────
            ['code' => 'DIR',       'name' => 'Direktur Utama',       'level' => 'Direktur',      'has_subordinates' => true],
            ['code' => 'MGR-PRD',   'name' => 'Manager Produksi',      'level' => 'Manager',       'has_subordinates' => true],
            ['code' => 'KB-PRD',    'name' => 'Kepala Bagian',         'level' => 'Kepala Bagian', 'has_subordinates' => true],

            // ── Staff fungsional (dari kolom Level Excel) ─────────────
            ['code' => 'HRGA',      'name' => 'Staff HRGA',            'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'ACC',       'name' => 'Staff Accounting',       'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'ASS-ACC',   'name' => 'Asisten Accounting',     'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'PPIC-EXIM', 'name' => 'Staff PPIC & EXIM',     'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'QA',        'name' => 'Staff QA',               'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'PURC',      'name' => 'Staff Purchasing',       'level' => 'Staff',         'has_subordinates' => false],
            ['code' => 'ENG-STAFF', 'name' => 'Staff Engineering',      'level' => 'Staff',         'has_subordinates' => false],

            // ── Operator per bagian produksi ──────────────────────────
            ['code' => 'OPR-P1',    'name' => 'Operator P1 (Sewing & QC Sewing)',   'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-P2',    'name' => 'Operator P2 (Meter, Setting, Naiki)','level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-P3',    'name' => 'Operator P3 (Latex)',                'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-P4',    'name' => 'Operator P4 (Machining)',            'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-P5',    'name' => 'Operator P5 (Prep Sponge)',          'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-P6',    'name' => 'Operator P6 (Dispo Cuff & ID Band)','level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-MNT',   'name' => 'Operator Maintenance',              'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-ENG',   'name' => 'Operator Engineering',              'level' => 'Operator', 'has_subordinates' => false],
            ['code' => 'OPR-GDG',   'name' => 'Operator Gudang, IQC & OQC',       'level' => 'Operator', 'has_subordinates' => false],

            // ── Non-produksi ──────────────────────────────────────────
            ['code' => 'SEC',       'name' => 'Security',               'level' => 'Security',     'has_subordinates' => false],
            ['code' => 'DRV',       'name' => 'Driver',                 'level' => 'Lainnya',      'has_subordinates' => false],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }
    }
}