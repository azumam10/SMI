<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;

final class LeaveValidationService
{
    public function __construct(
        private WorkingDayCalculator $calculator
    ) {}

    /**
     * Validasi seluruh pengajuan cuti.
     *
     * Return:
     * [
     *      'valid'=>bool,
     *      'message'=>string
     * ]
     */
    public function validate(
        Employee $employee,
        LeaveType $leaveType,
        Carbon|string $startDate,
        Carbon|string $endDate,
        int $documentCount = 0,
    ): array {

        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        /*
        |--------------------------------------------------------------------------
        | 1. tanggal
        |--------------------------------------------------------------------------
        */

        if ($startDate->greaterThan($endDate)) {

            return [
                'valid' => false,
                'message' => 'Tanggal mulai tidak boleh melebihi tanggal selesai.',
            ];

        }

        /*
        |--------------------------------------------------------------------------
        | 2. hitung hari kerja
        |--------------------------------------------------------------------------
        */

        $days = $this->calculator->calculate(
            $startDate,
            $endDate
        );

        if ($days <= 0) {

            return [
                'valid' => false,
                'message' => 'Rentang tanggal tidak memiliki hari kerja.',
            ];

        }

        /*
        |--------------------------------------------------------------------------
        | 3. cek kuota
        |--------------------------------------------------------------------------
        */

        $balance = LeaveBalance::query()

            ->where('employee_id', $employee->id)

            ->where('leave_type_id', $leaveType->id)

            ->where('year', $startDate->year)

            ->first();

        if (! $balance) {

            return [

                'valid' => false,

                'message' => 'Saldo cuti belum dibuat.',

            ];

        }

        if ($balance->remaining < $days) {

            return [

                'valid' => false,

                'message' => 'Sisa cuti tidak mencukupi.',

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | 4. overlap
        |--------------------------------------------------------------------------
        */

        $exists = LeaveRequest::query()

            ->where('employee_id', $employee->id)

            ->whereIn('status', [
                'pending',
                'supervisor_approved',
                'hrd_approved',
            ])

            ->where(function ($query) use ($startDate, $endDate) {

                $query

                    ->whereBetween('start_date', [
                        $startDate,
                        $endDate,
                    ])

                    ->orWhereBetween('end_date', [
                        $startDate,
                        $endDate,
                    ])

                    ->orWhere(function ($q) use ($startDate, $endDate) {

                        $q

                            ->where('start_date', '<=', $startDate)

                            ->where('end_date', '>=', $endDate);

                    });

            })

            ->exists();

        if ($exists) {

            return [

                'valid' => false,

                'message' => 'Tanggal cuti bertabrakan dengan pengajuan lain.',

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | 5. dokumen
        |--------------------------------------------------------------------------
        */

        if (

            $leaveType->require_document

            &&

            $documentCount === 0

        ) {

            return [

                'valid' => false,

                'message' => 'Dokumen pendukung wajib diupload.',

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | 6. pending sebelumnya
        |--------------------------------------------------------------------------
        */

        $pending = LeaveRequest::query()

            ->where('employee_id', $employee->id)

            ->whereIn('status', [

                'pending',

                'supervisor_approved',

            ])

            ->exists();

        if ($pending) {

            return [

                'valid' => false,

                'message' => 'Masih ada pengajuan cuti yang sedang diproses.',

            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return [

            'valid' => true,

            'message' => 'OK',

            'working_days' => $days,

        ];

        /*
        |--------------------------------------------------------------------------
        | Status karyawan
        |--------------------------------------------------------------------------
        */

        if (! $employee->is_active) {

            return [
                'valid' => false,
                'message' => 'Karyawan nonaktif tidak dapat mengajukan cuti.',
            ];

        }

        /*
        |--------------------------------------------------------------------------
        | Resign
        |--------------------------------------------------------------------------
        */

        if ($employee->resign_date !== null) {

            return [
                'valid' => false,
                'message' => 'Karyawan yang telah resign tidak dapat mengajukan cuti.',
            ];

        }
    }
}
