<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\EmployeeImportResult;
use App\Imports\EmployeeImport;
use App\Models\User;
use App\Services\EmployeeImportService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

final class ImportEmployeesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly int $requestedByUserId,
    ) {}

    public function handle(EmployeeImportService $service): void
    {
        $result = new EmployeeImportResult();
        $import = new EmployeeImport($service, $result);

        Excel::import($import, $this->filePath, 'local');

        // Gabungkan kegagalan validasi bawaan Maatwebsite ke laporan kita
        foreach ($import->failures() as $failure) {
            $result->addFailure(
                $failure->row(),
                (string) ($failure->values()['nik'] ?? '-'),
                implode(', ', $failure->errors())
            );
        }

        Storage::disk('local')->delete($this->filePath);

        $this->notifyRequester($result);
    }

    private function notifyRequester(EmployeeImportResult $result): void
    {
        $requester = User::find($this->requestedByUserId);

        if (! $requester) {
            return;
        }

        $notification = Notification::make()
            ->title('Import Karyawan Selesai')
            ->body($result->summary());

        $result->hasFailures() ? $notification->warning() : $notification->success();

        $notification->sendToDatabase($requester);
    }
}