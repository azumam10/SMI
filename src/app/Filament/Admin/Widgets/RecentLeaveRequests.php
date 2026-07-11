<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RecentLeaveRequests extends TableWidget
{
    protected static ?string $heading = 'Pengajuan Cuti Terbaru';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())

            ->defaultPaginationPageOption(10)

            ->defaultSort('submitted_at', 'desc')

            ->columns([

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Hari'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color),

            ]);
    }

    protected function getQuery(): Builder
    {
        $user = auth()->user();

        $query = LeaveRequest::query()
            ->with([
                'employee',
                'leaveType',
            ]);

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

        return $query->where(
            'employee_id',
            $employee->id
        );
    }
}
