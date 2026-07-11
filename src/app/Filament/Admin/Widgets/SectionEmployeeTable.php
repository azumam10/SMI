<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Section;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SectionEmployeeTable extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5; // urutan di dashboard (opsional)

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Section::query()
                    ->withCount([
                        'employees' => fn ($q) => $q->where('is_active', true)
                    ])
                    ->orderBy('name')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Section')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('employees_count')
                    ->label('Karyawan')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('percentage')
                    ->label('Persentase')
                    ->state(function (Section $record) {
                        // total semua karyawan aktif
                        $total = Section::withCount([
                            'employees' => fn ($q) => $q->where('is_active', true)
                        ])->get()->sum('employees_count');

                        if ($total == 0) {
                            return '0%';
                        }

                        $percent = round(($record->employees_count / $total) * 100, 1);
                        return $percent . '%';
                    })
                    ->alignRight(),
            ])
            ->actions([
                Action::make('lihat')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(function (Section $record) {
                        return '/admin/employees?tableFilters[section_id][value]=' . $record->id;
                    })
                    ->openUrlInNewTab(false),
            ])
            ->paginated(false); // tidak perlu paginasi untuk data sedikit
    }
}