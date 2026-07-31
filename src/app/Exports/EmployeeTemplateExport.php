<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class EmployeeTemplateExport implements FromArray, WithColumnWidths, WithEvents, WithHeadings, WithStyles
{
    // PENTING: heading di sini yang jadi acuan. Maatwebsite otomatis
    // ubah "Nama Lengkap" -> nama_lengkap, "NIK Atasan" -> nik_atasan, dst
    // Ini HARUS sama persis dengan key yang dipakai di EmployeeImportService.
    public function headings(): array
    {
        return [
            'NIK', 'Nama Lengkap', 'Nama Panggilan',
            'Departemen', 'Bagian', 'Jabatan',
            'Status Karyawan', 'Jenis Kelamin',
            'Tempat Lahir', 'Tanggal Lahir', 'Tanggal Bergabung', 'Tanggal Akhir Kontrak',
            'Pendidikan Terakhir', 'Jurusan',
            'Alamat KTP', 'Alamat Domisili', 'Kota', 'Provinsi', 'Kode Pos', 'No Telepon',
            'NIK Atasan',
        ];
    }

    public function array(): array
    {
        return [
            [
                'SMI-2025-001', 'Budi Santoso', 'Budi',
                'HRD', 'HRGA', 'Staff',
                'PKWT', 'L',
                'Jakarta', '17/08/1998', '01/01/2025', '',
                'S1', 'Manajemen',
                'Jl. Contoh No. 1', '', 'Tangerang', 'Banten', '15810', '081234567890',
                '',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16, 'B' => 25, 'C' => 18, 'D' => 16, 'E' => 16, 'F' => 16,
            'G' => 16, 'H' => 14, 'I' => 16, 'J' => 16, 'K' => 16, 'L' => 18,
            'M' => 18, 'N' => 18, 'O' => 25, 'P' => 25, 'Q' => 16, 'R' => 16,
            'S' => 12, 'T' => 16, 'U' => 16,
        ];
    }

    public function styles($sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->addDropdown($sheet, 'G', ['PKWTT', 'PKWT', 'HARIAN', 'DIREKTUR']);
                $this->addDropdown($sheet, 'H', ['L', 'P']);
            },
        ];
    }

    private function addDropdown(Worksheet $sheet, string $column, array $options): void
    {
        $validation = $sheet->getCell("{$column}2")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"'.implode(',', $options).'"');

        for ($row = 2; $row <= 500; $row++) {
            $sheet->getCell("{$column}{$row}")->setDataValidation(clone $validation);
        }
    }
}
