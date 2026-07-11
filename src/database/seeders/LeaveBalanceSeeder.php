<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Seeder;

final class LeaveBalanceSeeder extends Seeder
{
    public function run(): void
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
    }
}
