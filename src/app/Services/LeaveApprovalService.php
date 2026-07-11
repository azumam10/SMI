<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Exception;
use Illuminate\Support\Facades\DB;

final class LeaveApprovalService
{
    /**
     * Approve oleh Kepala Bagian
     */
    public function approveSupervisor(
        LeaveRequest $leave,
        int $supervisorId,
        ?string $note = null
    ): LeaveRequest {

        if ($leave->status !== 'pending') {
            throw new Exception('Status cuti sudah berubah.');
        }

        if ($leave->supervisor_id !== $supervisorId) {
            throw new Exception('Anda tidak berhak melakukan approval.');
        }

        DB::transaction(function () use ($leave, $supervisorId, $note) {

            $leave->update([

                'status' => 'supervisor_approved',

                'supervisor_id' => $supervisorId,

                'supervisor_note' => $note,

                'supervisor_approved_at' => now(),

            ]);

        });

        return $leave->fresh();
    }

    /**
     * Reject Kepala Bagian
     */
    public function rejectSupervisor(
        LeaveRequest $leave,
        int $supervisorId,
        ?string $note = null
    ): LeaveRequest {

        if ($leave->status !== 'pending') {
            throw new Exception('Status cuti sudah berubah.');
        }

        if ($leave->supervisor_id !== $supervisorId) {
            throw new Exception('Anda tidak berhak melakukan approval.');
        }

        DB::transaction(function () use ($leave, $supervisorId, $note) {

            $leave->update([

                'status' => 'supervisor_rejected',

                'supervisor_id' => $supervisorId,

                'supervisor_note' => $note,

                'supervisor_approved_at' => now(),

            ]);

        });

        return $leave->fresh();
    }

    /**
     * Approve HRD
     */
    public function approveHrd(
        LeaveRequest $leave,
        int $hrdId,
        ?string $note
    ): LeaveRequest {

        if (
            $leave->supervisor_id &&
            $leave->status !== 'supervisor_approved'
        ) {
            throw new Exception(
                'Pengajuan belum disetujui Kepala Bagian.'
            );
        }

        if (
            ! $leave->supervisor_id &&
            $leave->status !== 'pending'
        ) {
            throw new Exception(
                'Status cuti tidak valid.'
            );
        }

        DB::transaction(function () use ($leave, $hrdId, $note) {

            $approvedDays = $leave->total_days;

            $leave->update([

                'status' => 'hrd_approved',

                'hrd_id' => $hrdId,

                'hrd_note' => $note,

                'hrd_approved_at' => now(),

            ]);

            LeaveBalance::query()

                ->where('employee_id', $leave->employee_id)

                ->where('leave_type_id', $leave->leave_type_id)

                ->where('year', $leave->start_date->year)

                ->increment('used', $approvedDays);

        });

        return $leave->fresh();
    }

    /**
     * Reject HRD
     */
    public function rejectHrd(
        LeaveRequest $leave,
        int $hrdId,
        ?string $note = null
    ): LeaveRequest {

        if (
            $leave->supervisor_id &&
            $leave->status !== 'supervisor_approved'
        ) {
            throw new Exception(
                'Pengajuan belum disetujui Kepala Bagian.'
            );
        }

        if (
            ! $leave->supervisor_id &&
            $leave->status !== 'pending'
        ) {
            throw new Exception(
                'Status cuti tidak valid.'
            );
        }

        DB::transaction(function () use ($leave, $hrdId, $note) {

            $leave->update([

                'status' => 'hrd_rejected',

                'hrd_id' => $hrdId,

                'hrd_note' => $note,

                'hrd_approved_at' => now(),

            ]);

        });

        return $leave->fresh();
    }

    /**
     * Cancel
     */
    public function cancel(
        LeaveRequest $leave,
        int $userId
    ): LeaveRequest {
        if ($leave->employee->user_id !== $userId) {
            throw new Exception('Anda tidak berhak membatalkan cuti ini.');
        }

        if (! in_array($leave->status, [
            'pending',
            'supervisor_approved',
        ])) {

            throw new Exception('Cuti tidak bisa dibatalkan.');
        }

        DB::transaction(function () use ($leave, $userId) {

            $leave->update([

                'status' => 'cancelled',

                'cancelled_at' => now(),

                'cancelled_by' => $userId,

            ]);

        });

        return $leave->fresh();
    }
}
