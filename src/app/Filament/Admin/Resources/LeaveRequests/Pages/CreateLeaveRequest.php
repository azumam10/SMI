<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests\Pages;

use App\Filament\Admin\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Services\LeaveRequestService;
use App\Services\WorkingDayCalculator;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

final class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return app(LeaveRequestService::class)
            ->create($data);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()->hasRole(['super_admin', 'hrd'])) {
            $data['employee_id'] = auth()->user()->employee->id;
        }
        $employee = Employee::findOrFail($data['employee_id']);

        /*
        |--------------------------------------------------------------------------
        | 1. Cek status karyawan
        |--------------------------------------------------------------------------
        */

        if (! $employee->is_active) {

            Notification::make()
                ->danger()
                ->title('Karyawan tidak aktif')
                ->body('Karyawan yang sudah resign atau non aktif tidak dapat mengajukan cuti.')
                ->send();

            $this->halt();
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Hitung hari kerja
        |--------------------------------------------------------------------------
        */

        $calculator = app(WorkingDayCalculator::class);

        $days = $calculator->calculate(
            $data['start_date'],
            $data['end_date']
        );

        if ($days <= 0) {

            Notification::make()
                ->danger()
                ->title('Tanggal cuti tidak valid')
                ->send();

            $this->halt();
        }

        $data['total_days'] = $days;

        /*
        |--------------------------------------------------------------------------
        | 3. Cek saldo cuti
        |--------------------------------------------------------------------------
        */

        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $data['leave_type_id'])
            ->where('year', now()->year)
            ->first();

        if (! $balance) {

            Notification::make()
                ->danger()
                ->title('Saldo cuti belum dibuat')
                ->send();

            $this->halt();
        }

        if ($balance->remaining < $days) {

            Notification::make()
                ->danger()
                ->title('Saldo cuti tidak mencukupi')
                ->body("Sisa cuti hanya {$balance->remaining} hari.")
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Pengajuan cuti berhasil dibuat.';
    }
}
