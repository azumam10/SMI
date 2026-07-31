<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use App\Models\Section;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Actions\Action; 
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

final class SectionEmployeeTable extends BaseWidget
{
    protected static ?string $heading = 'Distribusi Karyawan Per Section';

    protected ?string $description = 'Jumlah dan persentase sebaran karyawan aktif berdasarkan seksi / unit kerja';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    /**
     * Property penampung cache total karyawan aktif (Mencegah N+1 Query)
     */
    private ?int $totalActiveEmployees = null;

    private function getTotalActiveEmployees(): int
    {
        return $this->totalActiveEmployees ??= Employee::query()
            ->where('is_active', true)
            ->whereNotNull('section_id')
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Section::query()
                    ->with(['department']) // Eager loading relasi departemen jika ada
                    ->withCount([
                        'employees' => fn ($q) => $q->where('is_active', true),
                    ])
                    ->orderBy('name')
            )
            ->striped()
            ->paginated(false)
            ->columns([
                Tables\Columns\TextColumn::make('no')
                    ->label('No')
                    ->rowIndex()
                    ->width('50px')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Seksi / Unit Kerja')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-rectangle-group')
                    ->iconColor('primary')
                    ->description(fn (Section $record): ?string => $record->department?->name ?? null),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Jumlah Karyawan')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-users')
                    ->suffix(' Orang')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Persentase')
                    ->state(function (Section $record): float {
                        $total = $this->getTotalActiveEmployees();

                        if ($total === 0) {
                            return 0.0;
                        }

                        return round(($record->employees_count / $total) * 100, 1);
                    })
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1) . '%')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 25.0 => 'success',
                        $state >= 10.0 => 'primary',
                        $state > 0.0   => 'warning',
                        default        => 'gray',
                    })
                    ->alignCenter(),
            ])
            ->actions([
                Action::make('lihat')
                    ->label('Lihat Karyawan')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->button()
                    ->size('xs')
                    ->color('gray')
                    ->url(fn (Section $record): string => "/admin/employees?tableFilters[section_id][value]={$record->id}"),
            ])
            ->emptyStateHeading('Belum Ada Data Section')
            ->emptyStateDescription('Tidak ada data seksi / unit kerja yang terdaftar di sistem.')
            ->emptyStateIcon('heroicon-o-rectangle-group');
    }

    public static function canView(): bool
{
    return auth()->user()?->hasAnyRole([
        'super_admin',
        'hrd',
    ]) ?? false;
}
}