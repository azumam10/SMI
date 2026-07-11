<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;

final class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        // Hanya untuk karyawan aktif
        if (! $employee->is_active) {
            return;
        }

        $year = now()->year;

        LeaveType::query()
            ->get()
            ->each(function (LeaveType $type) use ($employee, $year) {

                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $type->id,
                        'year' => $year,
                    ],
                    [
                        'quota' => $type->quota_days,
                        'used' => 0,
                    ]
                );

            });
    }
}
