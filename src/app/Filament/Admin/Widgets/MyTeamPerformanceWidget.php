<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\PerformanceReview;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyTeamPerformanceWidget extends TableWidget
{
    protected static ?string $heading = '👥 Performa Tim Saya'; // <-- static

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee || !$user->hasRole('kepala_bagian')) {
            return $table
                ->query(PerformanceReview::query()->whereRaw('1=0'))
                ->emptyStateHeading('Anda bukan Kepala Bagian')
                ->emptyStateDescription('Widget ini hanya untuk Kepala Bagian.');
        }

        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        $subordinateIds = Employee::where('supervisor_id', $employee->id)->pluck('id');

        if ($subordinateIds->isEmpty()) {
            return $table
                ->query(PerformanceReview::query()->whereRaw('1=0'))
                ->emptyStateHeading('Tidak ada bawahan')
                ->emptyStateDescription('Anda belum memiliki anggota tim.');
        }

        return $table
            ->query(
                PerformanceReview::query()
                    ->whereIn('employee_id', $subordinateIds)
                    ->where('year', $year)
                    ->where('semester', $semester)
                    ->where('status', PerformanceReview::STATUS_APPROVED)
                    ->with('employee')
                    ->orderByDesc('final_score')
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.id_number')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Outstanding' => 'success',
                        'Excellent'   => 'info',
                        'Good'        => 'warning',
                        'Fair'        => 'gray',
                        'Poor'        => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->getStatusBadgeColor())
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('final_score', 'desc')
            ->poll('30s')
            ->emptyStateHeading('Belum ada penilaian')
            ->emptyStateDescription('Tim Anda belum dinilai pada periode ini.');
    }

    public static function canView(): bool
    {
        return auth()->user()->hasRole('kepala_bagian');
    }
}