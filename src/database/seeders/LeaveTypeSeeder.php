<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run()
    {
        $types = [
            ['code' => 'annual', 'name' => 'Cuti Tahunan', 'quota_days' => 12, 'require_document' => false],
            ['code' => 'sick', 'name' => 'Cuti Sakit', 'quota_days' => 14, 'require_document' => true],
            ['code' => 'maternity', 'name' => 'Cuti Melahirkan', 'quota_days' => 90, 'require_document' => false],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}