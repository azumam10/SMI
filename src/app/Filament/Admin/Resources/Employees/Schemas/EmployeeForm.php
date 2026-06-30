<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

final class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ─── SECTION 1: Identitas ──────────────────────────────────────
                Section::make('Identitas Karyawan')
                    ->icon(Heroicon::UserCircle)
                    ->columns(3)
                    ->schema([
                        TextInput::make('id_number')
                            ->label('NIK Karyawan')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: SMI-2025-001'),
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nickname')
                            ->label('Nama Panggilan')
                            ->maxLength(255),
                    ]),

                // ─── SECTION 2: Organisasi ─────────────────────────────────────
                Section::make('Data Organisasi')
                    ->icon(Heroicon::BuildingOffice2)
                    ->columns(3)
                    ->schema([
                        Select::make('department_id')
                            ->label('Departemen')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('code')->label('Kode')->required()->maxLength(10),
                                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                            ]),
                        Select::make('section_id')
                            ->label('Bagian / Seksi')
                            ->relationship('section', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Select::make('department_id')
                                    ->label('Departemen')
                                    ->relationship('department', 'name')
                                    ->required()
                                    ->searchable(),
                                TextInput::make('code')->label('Kode')->required()->maxLength(20),
                                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                            ]),
                        Select::make('position_id')
                            ->label('Jabatan')
                            ->relationship('position', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('code')->label('Kode')->required()->maxLength(20),
                                TextInput::make('name')->label('Nama')->required()->maxLength(255),
                                Select::make('level')
                                    ->label('Level')
                                    ->required()
                                    ->options([
                                        'Direktur'      => 'Direktur',
                                        'Manager'       => 'Manager',
                                        'Kepala Bagian' => 'Kepala Bagian',
                                        'Supervisor'    => 'Supervisor',
                                        'Staff'         => 'Staff',
                                        'Operator'      => 'Operator',
                                        'Security'      => 'Security',
                                        'Lainnya'       => 'Lainnya',
                                    ]),
                            ]),
                    ]),

                // ─── SECTION 3: Status Kepegawaian ────────────────────────────
                Section::make('Status Kepegawaian')
                    ->icon(Heroicon::UserGroup)
                    ->columns(2)
                    ->schema([
                        Select::make('status_karyawan')
                            ->label('Status Karyawan')
                            ->required()
                            ->options([
                                'PKWTT'    => 'PKWTT (Tetap)',
                                'PKWT'     => 'PKWT (Kontrak)',
                                'HARIAN'   => 'Harian',
                                'DIREKTUR' => 'Direktur',
                            ])
                            ->default('PKWT'),
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                    ]),

                // ─── SECTION 4: Data Pribadi ──────────────────────────────────
                Section::make('Data Pribadi')
                    ->icon(Heroicon::User)
                    ->columns(4)
                    ->schema([
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(255),

                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()->subYears(17))
                            // Saat tanggal lahir berubah, update usia & generasi secara reactive
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if (! $state) return;
                                $year = (int) \Carbon\Carbon::parse($state)->format('Y');
                                $age  = \Carbon\Carbon::parse($state)->age;
                                $set('usia', $age);
                                $set('generation', Employee::resolveGeneration($year));
                            }),

                        TextInput::make('usia')
                            ->label('Usia')
                            ->numeric()
                            ->suffix('tahun')
                            ->disabled()
                            ->dehydrated(false) // hanya tampilan, nilai dihitung MySQL
                            ->helperText('Otomatis dari tanggal lahir'),

                        Select::make('generation')
                            ->label('Generasi')
                            ->options([
                                'Gen Z'        => 'Gen Z (1997+)',
                                'Milenial'     => 'Milenial (1981–1996)',
                                'Gen X'        => 'Gen X (1965–1980)',
                                'Baby Boomers' => 'Baby Boomers (≤1964)',
                            ])
                            ->disabled()
                            ->dehydrated(true) // generasi disimpan ke DB via model boot
                            ->helperText('Otomatis dari tahun lahir'),
                    ]),

                // ─── SECTION 5: Data Kepegawaian ──────────────────────────────
                Section::make('Data Kepegawaian')
                    ->icon(Heroicon::CalendarDays)
                    ->columns(2)
                    ->schema([
                        DatePicker::make('hire_date')
                            ->label('Tanggal Bergabung')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->maxDate(today()),
                        DatePicker::make('contract_end_date')
                            ->label('Tanggal Akhir Kontrak')
                            ->displayFormat('d/m/Y')
                            ->helperText('Isi jika status PKWT'),
                        TextInput::make('pendidikan')
                            ->label('Pendidikan Terakhir')
                            ->maxLength(255)
                            ->placeholder('SD, SLTP, SMA, SMK, D3, S1, S2, S3'),
                        TextInput::make('jurusan')
                            ->label('Jurusan')
                            ->maxLength(255),
                    ]),

                // ─── SECTION 6: Alamat & Kontak ──────────────────────────────
                Section::make('Alamat & Kontak')
                    ->icon(Heroicon::Home)
                    ->columns(2)
                    ->schema([
                        Textarea::make('alamat_ktp')
                            ->label('Alamat KTP')
                            ->rows(2)
                            ->columnSpanFull(),
                        Textarea::make('alamat_domisili')
                            ->label('Alamat Domisili')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Kosongkan jika sama dengan alamat KTP'),
                        TextInput::make('kota')
                            ->label('Kota')
                            ->maxLength(100),
                        TextInput::make('provinsi')
                            ->label('Provinsi')
                            ->maxLength(100),
                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->maxLength(10),
                        TextInput::make('no_telepon')
                            ->label('No. Telepon')
                            ->maxLength(20)
                            ->tel(),
                    ]),

                // ─── SECTION 7: Struktur Organisasi ──────────────────────────
                Section::make('Struktur Organisasi')
                    ->icon(Heroicon::RectangleGroup)
                    ->columns(2)
                    ->schema([
                        Select::make('supervisor_id')
                            ->label('Atasan Langsung')
                            ->options(fn () => Employee::query()
                                ->whereHas('position', fn ($q) => $q->whereIn('level', [
                                    'Direktur', 'Manager', 'Kepala Bagian', 'Supervisor',
                                ]))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Pilih atasan langsung'),
                        Select::make('user_id')
                            ->label('Akun Login')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Hubungkan dengan akun user'),
                    ]),

                // ─── SECTION 8: Status Keaktifan ─────────────────────────────
                Section::make('Status Keaktifan')
                    ->icon(Heroicon::CheckCircle)
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Karyawan Aktif')
                            ->default(true)
                            ->live(),
                        DatePicker::make('resign_date')
                            ->label('Tanggal Resign')
                            ->displayFormat('d/m/Y')
                            ->hidden(fn (Get $get) => $get('is_active') === true),
                        TextInput::make('resign_reason')
                            ->label('Alasan Resign')
                            ->maxLength(255)
                            ->hidden(fn (Get $get) => $get('is_active') === true),
                    ]),

                // ─── SECTION 9: Penilaian Kinerja (readonly) ─────────────────
                Section::make('Data Penilaian Kinerja')
                    ->icon(Heroicon::ChartBar)
                    ->columns(2)
                    ->description('Nilai diisi oleh sistem dari modul KPI, tidak dapat diedit manual.')
                    ->schema([
                        TextInput::make('performance_score')
                            ->label('Skor Kinerja')
                            ->numeric()
                            ->step(0.01)
                            ->readOnly()
                            ->placeholder('—'),
                        Select::make('performance_category')
                            ->label('Kategori Kinerja')
                            ->options([
                                'Low'  => 'Low',
                                'Med'  => 'Medium',
                                'High' => 'High',
                            ])
                            ->disabled()
                            ->dehydrated(false),
                    ]),
            ]);
    }
}