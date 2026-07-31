<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\PerformanceReview;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class MyTeamPerformanceWidget extends TableWidget
{
    protected static ?string $heading = 'Evaluasi & Performa Tim';

    protected ?string $description = 'Peringkat dan capaian kinerja anggota tim periode berjalan';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return auth()->user()->hasRole('kepala_bagian');
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee || ! $user->hasRole('kepala_bagian')) {
            return $table
                ->query(PerformanceReview::query()->whereRaw('1=0'))
                ->emptyStateHeading('Akses Khusus Kepala Bagian')
                ->emptyStateDescription('Widget ini hanya dapat diakses oleh Akun Kepala Bagian.')
                ->emptyStateIcon('heroicon-o-lock-closed');
        }

        $semester = now()->month <= 6 ? 1 : 2;
        $year = now()->year;

        $subordinateIds = Employee::where('supervisor_id', $employee->id)->pluck('id');

        if ($subordinateIds->isEmpty()) {
            return $table
                ->query(PerformanceReview::query()->whereRaw('1=0'))
                ->emptyStateHeading('Belum Ada Anggota Tim')
                ->emptyStateDescription('Anda belum memiliki bawahan langsung yang terdaftar di sistem.')
                ->emptyStateIcon('heroicon-o-user-group');
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
            ->striped()
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width('50px')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Anggota Tim')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary')
                    ->description(fn (PerformanceReview $record) => "NIK: " . ($record->employee->id_number ?? '-')),

                Tables\Columns\TextColumn::make('semester')
                    ->label('Periode')
                    ->formatStateUsing(fn ($record) => "Sem {$record->semester} ({$record->year})")
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai Akhir')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignCenter()
                    ->weight(FontWeight::Bold)
                    ->color(fn ($state) => match (true) {
                        $state >= 85 => 'success',
                        $state >= 70 => 'info',
                        $state >= 55 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori Kinerja')
                    ->badge()
                    ->alignCenter()
                    ->icon(fn (string $state): string => match ($state) {
                        'Outstanding' => 'heroicon-m-sparkles',
                        'Excellent'   => 'heroicon-m-star',
                        'Good'        => 'heroicon-m-hand-thumb-up',
                        'Fair'        => 'heroicon-m-minus-circle',
                        'Poor'        => 'heroicon-m-exclamation-triangle',
                        default       => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Outstanding' => 'success',
                        'Excellent'   => 'info',
                        'Good'        => 'warning',
                        'Fair'        => 'gray',
                        'Poor'        => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Evaluasi')
                    ->badge()
                    ->alignCenter()
                    ->color(fn ($record) => $record->getStatusBadgeColor())
                    ->formatStateUsing(fn ($record) => $record->getStatusLabel())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('final_score', 'desc')
            ->emptyStateHeading('Belum Ada Penilaian')
            ->emptyStateDescription('Hasil evaluasi kinerja tim Anda untuk semester ini belum tersedia atau belum disetujui.')
            ->emptyStateIcon('heroicon-o-chart-bar-square');
    }
}