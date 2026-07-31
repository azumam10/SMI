<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\LeaveRequest;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class RecentLeaveRequests extends TableWidget
{
    protected static ?string $heading = 'Pengajuan Cuti Terbaru';

    protected ?string $description = 'Daftar permohonan cuti terbaru karyawan beserta status persetujuannya';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->striped()
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width('50px')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary')
                    ->description(fn ($record) => $record->employee?->department?->name ?? 'Tanpa Departemen'),

                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('periode')
                    ->label('Periode Cuti')
                    ->getStateUsing(function ($record) {
                        if (! $record->start_date) return '-';
                        
                        $start = $record->start_date->format('d M Y');
                        $end = $record->end_date ? $record->end_date->format('d M Y') : $start;

                        return $start === $end ? $start : "{$start} - {$end}";
                    })
                    ->icon('heroicon-m-calendar-days')
                    ->iconColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Durasi')
                    ->numeric()
                    ->suffix(' Hari')
                    ->alignCenter()
                    ->weight(FontWeight::Bold)
                    ->sortable(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn ($record) => $record->submitted_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignCenter()
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color)
                    ->icon(fn ($record) => match ((string) $record->status) {
                        'approved', 'hrd_approved' => 'heroicon-m-check-circle',
                        'rejected', 'hrd_rejected' => 'heroicon-m-x-circle',
                        'pending'                  => 'heroicon-m-clock',
                        default                    => 'heroicon-m-minus-circle',
                    }),
            ])
            ->emptyStateHeading('Belum Ada Pengajuan Cuti')
            ->emptyStateDescription('Tidak ada riwayat atau antrean pengajuan cuti yang ditemukan.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    protected function getQuery(): Builder
    {
        $user = auth()->user();

        $query = LeaveRequest::query()
            ->with([
                'employee.department',
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