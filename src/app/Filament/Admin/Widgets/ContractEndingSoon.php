<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Employee;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class ContractEndingSoon extends TableWidget
{
    protected static ?string $heading = 'Kontrak Akan Berakhir';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getQuery())
            ->paginated(false)

            ->columns([

                Tables\Columns\TextColumn::make('id_number')
                    ->label('NIK')
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen'),

                Tables\Columns\TextColumn::make('position.name')
                    ->label('Jabatan'),

                Tables\Columns\TextColumn::make('contract_end_date')
                    ->label('Berakhir')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('remaining_days')
                    ->label('Sisa Hari')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 7 => 'danger',
                        $state <= 14 => 'warning',
                        default => 'info',
                    }),

            ]);
    }

    protected function getQuery(): Builder
    {
        return Employee::query()

            ->with([
                'department',
                'position',
            ])

            ->select('*')

            ->selectRaw('DATEDIFF(contract_end_date,CURDATE()) as remaining_days')

            ->where('status_karyawan', 'PKWT')

            ->where('is_active', true)

            ->whereNotNull('contract_end_date')

            ->whereBetween(
                'contract_end_date',
                [
                    today(),
                    today()->copy()->addDays(30),
                ]
            )

            ->orderBy('contract_end_date');
    }
}
