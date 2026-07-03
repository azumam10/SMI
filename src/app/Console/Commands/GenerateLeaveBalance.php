<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveBalance;

class GenerateLeaveBalance extends Command
{
    protected $signature = 'leave:generate-balance';

    protected $description = 'Generate leave balance untuk seluruh karyawan aktif';

    public function handle()
    {
        $employees = Employee::where('is_active', true)->get();

        $types = LeaveType::where('is_active', true)->get();

        foreach ($employees as $employee) {

            foreach ($types as $type) {

                LeaveBalance::firstOrCreate(
                    [
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $type->id,
                        'year'          => now()->year,
                    ],
                    [
                        'quota' => $type->quota_days,
                        'used'  => 0,
                    ]
                );

            }

        }

        $this->info('Leave balance berhasil dibuat.');
    }
}