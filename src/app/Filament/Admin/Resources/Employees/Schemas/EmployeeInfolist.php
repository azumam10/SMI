<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ─── SECTION 1: Identitas ──────────────────────────────────────
            Section::make('Identitas Karyawan')
                ->icon(Heroicon::UserCircle)
                ->columns(3)
                ->schema([
                    TextEntry::make('id_number')
                        ->label('NIK Karyawan')
                        ->copyable()
                        ->fontFamily('mono'),
                    TextEntry::make('name')
                        ->label('Nama Lengkap')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold),
                    TextEntry::make('nickname')
                        ->label('Nama Panggilan')
                        ->placeholder('—'),
                ]),

            // ─── SECTION 2: Organisasi ─────────────────────────────────────
            Section::make('Data Organisasi')
                ->icon(Heroicon::BuildingOffice2)
                ->columns(3)
                ->schema([
                    TextEntry::make('department.name')
                        ->label('Departemen')
                        ->placeholder('—')
                        ->badge()
                        ->color('primary'),
                    TextEntry::make('section.name')
                        ->label('Bagian / Seksi')
                        ->placeholder('—')
                        ->badge()
                        ->color('gray'),
                    TextEntry::make('position.name')
                        ->label('Jabatan')
                        ->placeholder('—')
                        ->badge()
                        ->color('info'),
                ]),

            // ─── SECTION 3: Status ─────────────────────────────────────────
            Section::make('Status Kepegawaian')
                ->icon(Heroicon::UserGroup)
                ->columns(4)
                ->schema([
                    TextEntry::make('status_karyawan')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'PKWTT' => 'primary',
                            'PKWT' => 'warning',
                            'HARIAN' => 'gray',
                            'DIREKTUR' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('gender')
                        ->label('Jenis Kelamin')
                        ->formatStateUsing(fn ($state) => $state === 'L' ? 'Laki-laki' : 'Perempuan')
                        ->badge()
                        ->color(fn ($state) => $state === 'L' ? 'info' : 'pink'),
                    TextEntry::make('generation')
                        ->label('Generasi')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'Gen Z' => 'info',
                            'Milenial' => 'success',
                            'Gen X' => 'warning',
                            'Baby Boomers' => 'danger',
                            default => 'gray',
                        })
                        ->placeholder('—'),
                    IconEntry::make('is_active')
                        ->label('Status Aktif')
                        ->boolean(),
                ]),

            // ─── SECTION 4: Data Pribadi ──────────────────────────────────
            Section::make('Data Pribadi')
                ->icon(Heroicon::User)
                ->columns(4)
                ->schema([
                    TextEntry::make('tempat_lahir')
                        ->label('Tempat Lahir')
                        ->placeholder('—'),
                    TextEntry::make('tanggal_lahir')
                        ->label('Tanggal Lahir')
                        ->date('d/m/Y'),
                    TextEntry::make('usia')
                        ->label('Usia')
                        ->suffix(' tahun')
                        ->placeholder('—'),
                    TextEntry::make('masa_kerja')
                        ->label('Masa Kerja')
                        ->getStateUsing(fn ($record) => $record->masa_kerja)
                        ->placeholder('—'),
                ]),

            // ─── SECTION 5: Kepegawaian ───────────────────────────────────
            Section::make('Data Kepegawaian')
                ->icon(Heroicon::CalendarDays)
                ->columns(4)
                ->schema([
                    TextEntry::make('hire_date')
                        ->label('Tanggal Bergabung')
                        ->date('d/m/Y'),
                    TextEntry::make('contract_end_date')
                        ->label('Akhir Kontrak')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('pendidikan')
                        ->label('Pendidikan')
                        ->placeholder('—'),
                    TextEntry::make('jurusan')
                        ->label('Jurusan')
                        ->placeholder('—'),
                ]),

            // ─── SECTION 6: Alamat & Kontak ──────────────────────────────
            Section::make('Alamat & Kontak')
                ->icon(Heroicon::Home)
                ->columns(2)
                ->schema([
                    TextEntry::make('alamat_ktp')
                        ->label('Alamat KTP')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('alamat_domisili')
                        ->label('Alamat Domisili')
                        ->placeholder('—')
                        ->columnSpanFull(),
                    TextEntry::make('kota')
                        ->label('Kota')
                        ->placeholder('—'),
                    TextEntry::make('provinsi')
                        ->label('Provinsi')
                        ->placeholder('—'),
                    TextEntry::make('kode_pos')
                        ->label('Kode Pos')
                        ->placeholder('—'),
                    TextEntry::make('no_telepon')
                        ->label('No. Telepon')
                        ->placeholder('—'),
                ]),

            // ─── SECTION 7: Struktur Organisasi ──────────────────────────
            Section::make('Struktur Organisasi')
                ->icon(Heroicon::RectangleGroup)
                ->columns(2)
                ->schema([
                    TextEntry::make('supervisor.name')
                        ->label('Atasan Langsung')
                        ->placeholder('—'),
                    TextEntry::make('user.email')
                        ->label('Akun Login')
                        ->placeholder('Belum dihubungkan'),
                ]),

            // ─── SECTION 8: Penilaian Kinerja ────────────────────────────
            Section::make('Penilaian Kinerja')
                ->icon(Heroicon::ChartBar)
                ->columns(2)
                ->schema([
                    TextEntry::make('performance_score')
                        ->label('Skor Kinerja')
                        ->numeric(decimalPlaces: 2)
                        ->placeholder('Belum dinilai'),
                    TextEntry::make('performance_category')
                        ->label('Kategori Kinerja')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'High' => 'success',
                            'Med' => 'warning',
                            'Low' => 'danger',
                            default => 'gray',
                        })
                        ->placeholder('Belum dinilai'),
                ]),

            // ─── SECTION 9: Resign (hanya tampil jika tidak aktif) ───────
            Section::make('Informasi Resign')
                ->icon(Heroicon::XCircle)
                ->columns(2)
                ->visible(fn (Employee $record) => ! $record->is_active)
                ->schema([
                    TextEntry::make('resign_date')
                        ->label('Tanggal Resign')
                        ->date('d/m/Y')
                        ->placeholder('—'),
                    TextEntry::make('resign_reason')
                        ->label('Alasan Resign')
                        ->placeholder('—'),
                ]),

            // ─── SECTION 10: Metadata ─────────────────────────────────────
            Section::make('Metadata')
                ->icon(Heroicon::InformationCircle)
                ->columns(3)
                ->collapsed()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Dibuat')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')
                        ->label('Diperbarui')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('deleted_at')
                        ->label('Dihapus')
                        ->dateTime('d/m/Y H:i')
                        ->placeholder('—')
                        ->visible(fn (Employee $record) => $record->trashed()),
                ]),
        ]);
    }
}
