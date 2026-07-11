<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Console\Command;

final class GenerateLeaveBalance extends Command
{
    protected $signature = 'leave:generate-balance';

    protected $description = 'Generate leave balance untuk seluruh karyawan aktif';

    public function handle()
    {
        $employees = Employee::where('is_active', true)->get();

        $types = LeaveType::all();
        foreach ($employees as $employee) {

            foreach ($types as $type) {

                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => now()->year,
                    ],
                    [
                        'quota' => $type->quota_days,
                        'used' => 0,
                    ]
                );

            }

        }

        $this->info('Leave balance berhasil dibuat.');
    }
}
