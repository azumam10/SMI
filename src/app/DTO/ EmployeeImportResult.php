<?php

declare(strict_types=1);

namespace App\DTO;

final class EmployeeImportResult
{
    /** @var array<int, string> */
    private array $successes = [];

    /** @var array<int, array{row: int, nik: string, message: string}> */
    private array $failures = [];

    public function addSuccess(string $nik): void
    {
        $this->successes[] = $nik;
    }

    public function addFailure(int $row, string $nik, string $message): void
    {
        $this->failures[] = [
            'row' => $row,
            'nik' => $nik,
            'message' => $message,
        ];
    }

    public function successCount(): int
    {
        return count($this->successes);
    }

    public function failureCount(): int
    {
        return count($this->failures);
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }

    /** @return array<int, array{row: int, nik: string, message: string}> */
    public function failures(): array
    {
        return $this->failures;
    }

    public function summary(): string
    {
        $text = "{$this->successCount()} karyawan berhasil diimport.";

        if ($this->hasFailures()) {
            $text .= " {$this->failureCount()} baris gagal.";
        }

        return $text;
    }
}