<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\EmployeeImportResult;
use App\Exceptions\RowImportException;
use App\Services\EmployeeImportService;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Row;

final class EmployeeImport implements OnEachRow, WithHeadingRow, WithValidation, SkipsOnFailure, WithChunkReading
{
    use SkipsFailures;

    public function __construct(
        private readonly EmployeeImportService $service,
        private readonly EmployeeImportResult $result,
    ) {}

    public function onRow(Row $row): void
    {
        $rowNumber = $row->getIndex();
        $data = $row->toArray();
        $nik = trim((string) ($data['nik'] ?? '-'));

        try {
            $this->service->importRow($data);
            $this->result->addSuccess($nik);
        } catch (RowImportException $e) {
            $this->result->addFailure($rowNumber, $nik, $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->result->addFailure($rowNumber, $nik, 'Kesalahan sistem: '.$e->getMessage());
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:20'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'departemen' => ['required', 'string'],
            'bagian' => ['required', 'string'],
            'jabatan' => ['required', 'string'],
            'status_karyawan' => ['required', Rule::in(['PKWTT', 'PKWT', 'HARIAN', 'DIREKTUR'])],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P', 'l', 'p'])],
            'tanggal_lahir' => ['required'],
            'tanggal_bergabung' => ['required'],
            'nik_atasan' => ['nullable', 'string'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nama_lengkap.required' => 'Nama Lengkap wajib diisi.',
            'departemen.required' => 'Departemen wajib diisi.',
            'bagian.required' => 'Bagian/Seksi wajib diisi.',
            'jabatan.required' => 'Jabatan wajib diisi.',
            'status_karyawan.in' => 'Status Karyawan harus: PKWTT, PKWT, HARIAN, atau DIREKTUR.',
            'jenis_kelamin.in' => 'Jenis Kelamin harus L atau P.',
            'tanggal_lahir.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_bergabung.required' => 'Tanggal Bergabung wajib diisi.',
        ];
    }
}