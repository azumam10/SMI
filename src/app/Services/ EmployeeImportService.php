<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RowImportException;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class EmployeeImportService
{
    /**
     * NIK yang sudah diproses dalam SATU sesi import ini.
     * Perlu untuk menangkap NIK duplikat DI DALAM file yang sama,
     * karena validasi unique:employees tidak menangkap itu
     * (baris divalidasi per-chunk sebelum baris lain benar2 tersimpan).
     *
     * @var array<string, true>
     */
    private array $processedNiks = [];

    private const LEVEL_ROLE_MAP = [
        'Kepala Bagian' => 'kepala_bagian',
    ];

    private const HRD_DEPARTMENT_KEYWORDS = ['hrd', 'hrga'];

    private const DEFAULT_ROLE = 'karyawan';

    private const EMAIL_DOMAIN = 'sankei.com';

    public function importRow(array $row): Employee
    {
        $nik = trim((string) ($row['nik'] ?? ''));

        if ($nik === '') {
            throw new RowImportException('NIK kosong.');
        }

        if (isset($this->processedNiks[$nik]) || Employee::where('id_number', $nik)->exists()) {
            throw new RowImportException("NIK {$nik} sudah terdaftar, baris dilewati.");
        }

        $department = $this->findByName(Department::class, $row['departemen'] ?? null, 'Departemen');
        $section = $this->findByName(Section::class, $row['bagian'] ?? null, 'Bagian/Seksi');
        $position = $this->findByName(Position::class, $row['jabatan'] ?? null, 'Jabatan');

        $tanggalLahir = $this->parseDate($row['tanggal_lahir'] ?? null);
        if (! $tanggalLahir) {
            throw new RowImportException('Tanggal Lahir kosong / format salah (pakai dd/mm/yyyy).');
        }

        $hireDate = $this->parseDate($row['tanggal_bergabung'] ?? null);
        if (! $hireDate) {
            throw new RowImportException('Tanggal Bergabung kosong / format salah (pakai dd/mm/yyyy).');
        }

        $supervisorId = null;
        $nikAtasan = trim((string) ($row['nik_atasan'] ?? ''));
        if ($nikAtasan !== '') {
            $supervisor = Employee::where('id_number', $nikAtasan)->first();
            if (! $supervisor) {
                throw new RowImportException("NIK Atasan {$nikAtasan} tidak ditemukan.");
            }
            $supervisorId = $supervisor->id;
        }

        return DB::transaction(function () use (
            $row, $nik, $department, $section, $position,
            $tanggalLahir, $hireDate, $supervisorId
        ) {
            $employee = Employee::create([
                'id_number' => $nik,
                'name' => trim((string) $row['nama_lengkap']),
                'nickname' => $row['nama_panggilan'] ?? null,
                'department_id' => $department->id,
                'section_id' => $section->id,
                'position_id' => $position->id,
                'status_karyawan' => strtoupper((string) $row['status_karyawan']),
                'gender' => strtoupper((string) $row['jenis_kelamin']),
                'tempat_lahir' => $row['tempat_lahir'] ?? null,
                'tanggal_lahir' => $tanggalLahir,
                'hire_date' => $hireDate,
                'contract_end_date' => $this->parseDate($row['tanggal_akhir_kontrak'] ?? null),
                'pendidikan' => $row['pendidikan_terakhir'] ?? null,
                'jurusan' => $row['jurusan'] ?? null,
                'alamat_ktp' => $row['alamat_ktp'] ?? null,
                'alamat_domisili' => $row['alamat_domisili'] ?? null,
                'kota' => $row['kota'] ?? null,
                'provinsi' => $row['provinsi'] ?? null,
                'kode_pos' => $row['kode_pos'] ?? null,
                'no_telepon' => $row['no_telepon'] ?? null,
                'supervisor_id' => $supervisorId,
                'is_active' => true,
                // 'generation' otomatis diisi oleh Employee::boot() saat saving
            ]);

            $user = $this->createUserAccount($employee, $tanggalLahir);
            $employee->update(['user_id' => $user->id]);
            $user->syncRoles([$this->determineRole($employee, $department)]);

            $this->processedNiks[$nik] = true;

            return $employee;
        });
    }

    private function createUserAccount(Employee $employee, Carbon $tanggalLahir): User
    {
        $baseUsername = $this->generateUsername($employee->name);
        $username = $baseUsername;
        $email = "{$username}@".self::EMAIL_DOMAIN;

        $suffix = 1;
        while (User::where('email', $email)->exists()) {
            $suffix++;
            $username = $baseUsername.$suffix;
            $email = "{$username}@".self::EMAIL_DOMAIN;
        }

        return User::create([
            'name' => $employee->name,
            'email' => $email,
            // cast 'hashed' di model User -> otomatis di-hash saat disimpan
            'password' => $tanggalLahir->format('dmY'),
        ]);
    }

    private function generateUsername(string $fullName): string
    {
        $words = preg_split('/\s+/', trim($fullName)) ?: [];
        $firstTwo = array_slice($words, 0, 2); // nama depan + tengah

        $username = strtolower(implode('', $firstTwo));
        $username = preg_replace('/[^a-z0-9]/', '', $username);

        return $username !== '' ? $username : 'karyawan';
    }

    private function determineRole(Employee $employee, Department $department): string
    {
        $departmentName = mb_strtolower($department->name);

        foreach (self::HRD_DEPARTMENT_KEYWORDS as $keyword) {
            if (str_contains($departmentName, $keyword)) {
                return 'hrd';
            }
        }

        $level = $employee->position?->level;

        return self::LEVEL_ROLE_MAP[$level] ?? self::DEFAULT_ROLE;
    }

    private function findByName(string $modelClass, mixed $name, string $label): mixed
    {
        $name = trim((string) $name);

        if ($name === '') {
            throw new RowImportException("{$label} kosong.");
        }

        $record = $modelClass::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if (! $record) {
            throw new RowImportException("{$label} '{$name}' tidak ditemukan di sistem. Pastikan nama sama persis dengan yang ada di database.");
        }

        return $record;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }
}