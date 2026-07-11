<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ── Buat akun HRD admin ───────────────────────────────────────
        $hrdUser = User::firstOrCreate(
            ['email' => 'hrd@sankei.com'],
            [
                'name' => 'Admin HRD',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // ── Helper closures ───────────────────────────────────────────
        $dept = fn ($code) => Department::where('code', $code)->first();
        $pos = fn ($code) => Position::where('code', $code)->first();
        $sec = fn ($code) => Section::where('code', $code)->first();

        // ─────────────────────────────────────────────────────────────
        // DIREKTUR UTAMA
        // ─────────────────────────────────────────────────────────────
        $direktur = Employee::firstOrCreate(
            ['id_number' => '971004'],
            [
                'name' => 'YOSHINORI KOYAMA',
                'gender' => 'L',
                'tanggal_lahir' => '1974-01-26',
                'hire_date' => '1997-10-31',
                'department_id' => $dept('PROD')?->id,
                'position_id' => $pos('DIR')?->id,
                'section_id' => $sec('DIR-UTAMA')?->id,
                'status_karyawan' => 'PKWTT',
                'pendidikan' => 'S1',
                'performance_score' => 2.72,
                'performance_category' => 'Med',
                'is_active' => true,
                'user_id' => $hrdUser->id,
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // MANAGER PRODUKSI
        // ─────────────────────────────────────────────────────────────
        $mgrProd = Employee::firstOrCreate(
            ['id_number' => '1234567'],
            [
                'name' => 'KIMURA KENJI',
                'gender' => 'L',
                'tanggal_lahir' => '1980-09-26',
                'hire_date' => '2025-10-09',
                'department_id' => $dept('PROD')?->id,
                'position_id' => $pos('MGR-PRD')?->id,
                'section_id' => $sec('MGR-PROD')?->id,
                'status_karyawan' => 'PKWT',
                'pendidikan' => 'S1',
                'performance_score' => 2.75,
                'performance_category' => 'Med',
                'supervisor_id' => null, // di-update setelah direktur
                'is_active' => true,
            ]
        );

        // ─────────────────────────────────────────────────────────────
        // STAFF (berbagai fungsi)
        // ─────────────────────────────────────────────────────────────
        $staffData = [
            ['9410007', 'DENI R SUHAR',    'ACC',      'L', '1970-07-16', '1994-10-10', 'PKWT',  'S1',  2.75,  'Med'],
            ['103089',  'TITAH S',         'ASS-ACC',  'P', '1979-10-22', '2001-03-10', 'PKWTT', 'SMK', 2.75,  'Med'],
            ['4083',    'WALUYO BP',       'HRGA',     'L', '1974-01-26', '2000-10-04', 'PKWTT', 'S1',  2.79,  'Med'],
            ['9608006', 'SUMIYATI',        'PPIC-EXIM', 'P', '1978-02-18', '1996-08-08', 'PKWTT', 'SMK', 2.82,  'Med'],
            ['1612317', "FU'AD",            'QA',       'L', '1994-05-23', '2016-12-03', 'PKWTT', 'D3',  2.83,  'Med'],
            ['9908079', 'ELISABETH SIP',   'PURC',     'P', '1973-10-31', '1999-08-10', 'PKWTT', 'S1',  2.83,  'Med'],
            ['2406524', 'PUPUT',           'PURC',     'P', '1995-02-01', '2024-04-10', 'PKWTT', 'S1',  2.83,  'Med'],
            ['2406525', 'WILDAN',          'ASS-ACC',  'L', '2001-04-02', '2024-06-05', 'PKWT',  'S1',  2.83,  'Med'],
            ['2301521', 'HEDRWIN SUR',     'ENG-STAFF', 'L', '1995-04-18', '2021-03-04', 'PKWTT', 'SMK', 2.70,  'Med'],
        ];

        $staffMap = []; // simpan untuk referensi supervisor
        foreach ($staffData as [$id, $name, $posCode, $gender, $tgl, $hire, $status, $pendidikan, $score, $kinerja]) {
            $staffMap[$id] = Employee::firstOrCreate(
                ['id_number' => $id],
                [
                    'name' => $name,
                    'gender' => $gender,
                    'tanggal_lahir' => $tgl,
                    'hire_date' => $hire,
                    'department_id' => $dept('PROD')?->id,
                    'position_id' => $pos($posCode)?->id,
                    'section_id' => $sec('STAFF')?->id,
                    'status_karyawan' => $status,
                    'pendidikan' => $pendidikan,
                    'performance_score' => $score,
                    'performance_category' => $kinerja,
                    'supervisor_id' => $direktur->id,
                    'is_active' => true,
                ]
            );
        }

        // Link akun HRD ke Waluyo BP
        if (isset($staffMap['4083'])) {
            $staffMap['4083']->update(['user_id' => $hrdUser->id]);
        }

        // ─────────────────────────────────────────────────────────────
        // KEPALA BAGIAN (8 orang dari data real)
        // ─────────────────────────────────────────────────────────────
        $kbData = [
            ['9803028', 'BUDI WAHYUDI', 'P', '1973-02-15', '1998-03-10', 'SMA',  2.83, 'Med'],
            ['9410002', 'SURATMAN',     'L', '1974-01-24', '1994-01-10', 'SMK',  2.83, 'Med'],
            ['9810058', 'ERNI JOHAN',   'P', '1976-02-20', '1998-10-10', 'SLTP', 2.87, 'Med'],
            ['9401004', 'NURHOLID',     'L', '1974-08-07', '1994-01-10', 'SMA',  2.90, 'Med'],
            ['9710020', 'SOFIATUN',     'P', '1976-09-03', '1997-10-01', 'SMK',  2.93, 'Med'],
            ['9210002', 'SALIM',        'L', '1971-09-28', '1992-10-10', 'SMK',  3.05, 'High'],
            ['5024',    'ABAD',         'L', '1977-01-17', '2000-08-05', 'SMK',  3.07, 'High'],
            ['9707017', 'TITIN S',      'P', '1982-01-03', '1997-06-07', 'SLTP', 3.08, 'High'],
        ];

        $kbMap = [];
        foreach ($kbData as [$id, $name, $gender, $tgl, $hire, $pendidikan, $score, $kinerja]) {
            $kbMap[$id] = Employee::firstOrCreate(
                ['id_number' => $id],
                [
                    'name' => $name,
                    'gender' => $gender,
                    'tanggal_lahir' => $tgl,
                    'hire_date' => $hire,
                    'department_id' => $dept('PROD')?->id,
                    'position_id' => $pos('KB-PRD')?->id,
                    'section_id' => $sec('KB')?->id,
                    'status_karyawan' => 'PKWTT',
                    'pendidikan' => $pendidikan,
                    'performance_score' => $score,
                    'performance_category' => $kinerja,
                    'supervisor_id' => $mgrProd->id,
                    'is_active' => true,
                ]
            );
        }

        // Ambil kepala bagian pertama sebagai supervisor operator
        $kbFirst = reset($kbMap);

        // ─────────────────────────────────────────────────────────────
        // OPERATOR (2 per bagian, data real)
        // ─────────────────────────────────────────────────────────────
        $operatorData = [
            // [id_number, nama, posCode, secCode, gender, tgl_lahir, hire_date, status, pendidikan, score, kinerja]
            ['9801023', 'RATIH',           'OPR-P1', 'P1-SEW',  'P', '1976-07-12', '1998-01-10', 'PKWTT', 'SD',  1.33, 'Low'],
            ['2103439', 'NUR SUCIATI',     'OPR-P1', 'P1-SEW',  'P', '1986-08-23', '2025-09-08', 'PKWT',  'SMA', 1.33, 'Low'],
            ['108098',  'KAMSINAH',        'OPR-P2', 'P2-MTR',  'P', '1981-06-02', '2001-07-08', 'PKWTT', 'SMA', 1.58, 'Low'],
            ['9806043', 'LAMSIAH',         'OPR-P2', 'P2-MTR',  'P', '1979-04-05', '1998-06-16', 'PKWTT', 'SLTP', 1.58, 'Low'],
            ['2103445', 'JUMROH',          'OPR-P3', 'P3-LAT',  'P', '1987-09-11', '2021-06-20', 'PKWT',  'SMA', 1.75, 'Low'],
            ['8085',    'ARDANI',          'OPR-P4', 'P4-MCH',  'L', '1981-03-08', '2000-08-10', 'PKWTT', 'SMK', 1.77, 'Low'],
            ['9088',    'M. ISMAIL',       'OPR-P4', 'P4-MCH',  'L', '1980-12-03', '2000-09-09', 'PKWTT', 'SMK', 1.83, 'Low'],
            ['2008405', 'HANAFI',          'OPR-P5', 'P5-PREP', 'L', '1993-05-10', '2020-08-04', 'PKWT',  'SMK', 1.92, 'Low'],
            ['2008407', 'MUHAMMAD HAMAMI', 'OPR-P5', 'P5-PREP', 'L', '1985-08-27', '2020-08-04', 'PKWT',  'SMA', 1.92, 'Low'],
            ['2008402', 'PARASIAN ED',     'OPR-P6', 'P6-DSP',  'L', '2001-08-10', '2020-06-15', 'PKWT',  'SMK', 2.25, 'Low'],
            ['2210512', 'MIRA ERIA',       'OPR-P6', 'P6-DSP',  'P', '1999-05-13', '2022-01-31', 'PKWT',  'SMA', 2.28, 'Low'],
            ['2103452', 'GAGUKSUNAR',      'OPR-MNT', 'MNT',     'L', '1982-01-28', '2021-03-04', 'PKWTT', 'SMA', 2.67, 'Med'],
            ['2112503', 'ACUN',            'OPR-MNT', 'MNT',     'L', '1987-02-21', '2021-12-05', 'PKWT',  'SMA', 2.67, 'Med'],
            ['2008404', 'AHMAD NUR',       'OPR-ENG', 'ENG',     'L', '2002-04-17', '2020-08-04', 'PKWTT', 'S1',  2.67, 'Med'],
            ['2110500', 'ANDI JUNAEDI',    'OPR-GDG', 'GDG',     'L', '1990-02-03', '2021-10-05', 'PKWT',  'SMK', 2.70, 'Med'],
            ['2401528', 'FADHILAH UTAMI',  'OPR-GDG', 'GDG',     'P', '1999-07-19', '2024-07-16', 'PKWT',  'SMK', 2.70, 'Med'],
            ['1812330', 'IKHSAN S',        'SEC',    'SECURITY', 'L', '1987-06-20', '2018-12-03', 'PKWT',  'SMK', 3.16, 'High'],
            ['1812334', 'YAYAH ROHAYAH',   'SEC',    'SECURITY', 'P', '1992-03-11', '2017-06-16', 'PKWT',  'SMA', 3.18, 'High'],
            ['2011414', 'HERU SUTIYONO',   'DRV',    'DRIVER',  'L', '1982-11-03', '2020-01-09', 'PKWT',  'SMK', 3.40, 'High'],
            ['1233211', 'HARRY SUSANTO',   'DRV',    'DRIVER',  'L', '1982-10-05', '2025-09-24', 'HARIAN', 'SMA', 3.42, 'High'],
        ];

        foreach ($operatorData as [$id, $name, $posCode, $secCode, $gender, $tgl, $hire, $status, $pendidikan, $score, $kinerja]) {
            Employee::firstOrCreate(
                ['id_number' => $id],
                [
                    'name' => $name,
                    'gender' => $gender,
                    'tanggal_lahir' => $tgl,
                    'hire_date' => $hire,
                    'department_id' => $dept('PROD')?->id,
                    'position_id' => $pos($posCode)?->id,
                    'section_id' => $sec($secCode)?->id,
                    'status_karyawan' => $status,
                    'pendidikan' => $pendidikan,
                    'performance_score' => $score,
                    'performance_category' => $kinerja,
                    'supervisor_id' => $kbFirst->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
