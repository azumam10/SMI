<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class EmployeesOnLeaveToday extends TableWidget
{
    protected static ?string $heading = 'Karyawan Sedang Cuti Hari Ini';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->paginated(false)
            ->striped()

            // ── Tampilan Khusus Jika Tidak Ada Data Cuti ─────────────
            ->emptyStateHeading('Semua Karyawan Hadir')
            ->emptyStateDescription('Tidak ada karyawan yang sedang menjalani cuti pada hari ini.')
            ->emptyStateIcon('heroicon-o-check-badge')

            ->columns([
                // 1. Kolom Nama & NIK (Di-merge agar lebih ringkas)
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->description(fn (LeaveRequest $record): ?string => $record->employee?->id_number ? "NIK: {$record->employee->id_number}" : null)
                    ->searchable()
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary'),

                // 2. Departemen dengan Badge Gray
                Tables\Columns\TextColumn::make('employee.department.name')
                    ->label('Departemen')
                    ->badge()
                    ->color('gray')
                    ->default('-'),

                // 3. Jenis Cuti dengan Badge Warning & Ikon
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-m-document-text')
                    ->default('-'),

                // 4. Tanggal Mulai
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai Cuti')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->iconColor('gray')
                    ->sortable(),

                // 5. Tanggal Selesai
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai Cuti')
                    ->date('d M Y')
                    ->icon('heroicon-m-calendar')
                    ->iconColor('gray')
                    ->sortable(),

                // 6. Total Durasi Cuti (Badge Info)
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).' Hari')
                    ->badge()
                    ->color('info')
                    ->alignment(Alignment::Center),
            ]);
    }

    protected function getQuery(): Builder
    {
        $user = auth()->user();

        $query = LeaveRequest::query()
            ->with([
                'employee.department',
                'leaveType',
            ])
            ->where('status', 'hrd_approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today());

        if (
            $user->hasRole('super_admin') ||
            $user->hasRole('hrd')
        ) {
            return $query;
        }

        $employee = $user->employee;

        if (! $employee) {
            return $query->whereKey([]);
        }

        if ($user->hasRole('kepala_bagian')) {
            $ids = $employee
                ->subordinates()
                ->pluck('id')
                ->push($employee->id);

            return $query->whereIn('employee_id', $ids);
        }

        return $query->where('employee_id', $employee->id);
    }

    public static function canView(): bool
{
    return auth()->user()?->hasAnyRole([
        'super_admin',
        'hrd',
    ]) ?? false;
}
}
