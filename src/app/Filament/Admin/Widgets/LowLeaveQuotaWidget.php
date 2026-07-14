<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class LowLeaveQuotaWidget extends TableWidget
{
    protected static ?string $heading = '⚠️ Karyawan dengan Sisa Cuti < 7 Hari';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        $year = now()->year;
        $quotaPerYear = 12;

        // ── Query dengan subquery untuk menghitung sisa cuti ──
        $query = Employee::query()
            ->where('is_active', true)
            ->select(
                'employees.*',
                // Subquery untuk total cuti terpakai
                DB::raw("(
                    SELECT COALESCE(SUM(total_days), 0)
                    FROM leave_requests
                    WHERE leave_requests.employee_id = employees.id
                    AND leave_requests.status = 'hrd_approved'
                    AND YEAR(leave_requests.start_date) = {$year}
                ) as used_leave"),
                // Subquery untuk sisa cuti
                DB::raw("(
                    {$quotaPerYear} - COALESCE(
                        (SELECT SUM(total_days)
                         FROM leave_requests
                         WHERE leave_requests.employee_id = employees.id
                         AND leave_requests.status = 'hrd_approved'
                         AND YEAR(leave_requests.start_date) = {$year}),
                    0)
                ) as remaining_quota")
            )
            // Hanya ambil yang sisa cuti < 7 hari
            ->havingRaw('remaining_quota < 7')
            // Urutkan dari yang paling sedikit
            ->orderBy('remaining_quota', 'asc');

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable(),

                Tables\Columns\TextColumn::make('position.name')
                    ->label('Jabatan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('used_leave')
                    ->label('Cuti Terpakai')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' Hari')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_quota')
                    ->label('Sisa Cuti')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(' Hari')
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'info')),

                Tables\Columns\TextColumn::make('status_cuti')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $remaining = $record->remaining_quota ?? 0;
                        if ($remaining <= 0) return 'Habis';
                        if ($remaining <= 3) return 'Kritis';
                        return 'Menipis';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Habis' => 'danger',
                        'Kritis' => 'warning',
                        'Menipis' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('remaining_quota', 'asc')
            ->emptyStateHeading('Tidak ada karyawan dengan sisa cuti < 7 hari')
            ->emptyStateDescription('Semua karyawan masih memiliki kuota cuti yang cukup.')
            ->poll('60s');
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd', 'kepala_bagian']);
    }
}