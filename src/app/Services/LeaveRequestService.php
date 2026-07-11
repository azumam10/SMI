<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

final class LeaveRequestService
{
    public function create(array $data): LeaveRequest
    {
        return DB::transaction(function () use ($data) {

            $employee = Employee::findOrFail($data['employee_id']);

            /*
            |--------------------------------------------------------------------------
            | Cari jenis cuti
            |--------------------------------------------------------------------------
            */

            $leaveType = LeaveType::findOrFail(
                $data['leave_type_id']
            );

            /*
            |--------------------------------------------------------------------------
            | Cari saldo
            |--------------------------------------------------------------------------
            */

            $balance = LeaveBalance::firstOrCreate(

                [
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => now()->year,
                ],

                [
                    'quota' => $leaveType->quota_days,
                    'used' => 0,
                ]

            );

            /*
            |--------------------------------------------------------------------------
            | Supervisor
            |--------------------------------------------------------------------------
            */

            $data['supervisor_id'] = $employee->supervisor_id;

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $data['status'] = 'pending';

            /*
            |--------------------------------------------------------------------------
            | Submitted
            |--------------------------------------------------------------------------
            */

            $data['submitted_at'] = now();

            return LeaveRequest::create($data);

        });
    }
}
