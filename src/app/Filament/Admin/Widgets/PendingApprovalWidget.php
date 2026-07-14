<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingApprovalWidget extends TableWidget
{
    protected static ?string $heading = '⏳ Penilaian Menunggu Approval'; // <-- static

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PerformanceReview::query()
                    ->where('status', PerformanceReview::STATUS_SUBMITTED)
                    ->with(['employee', 'reviewer'])
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Penilai')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('semester_label')
                    ->label('Periode')
                    ->getStateUsing(fn ($record) => "S{$record->semester} / {$record->year}"),

                Tables\Columns\TextColumn::make('final_score')
                    ->label('Nilai')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->date('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('warning')
                    ->formatStateUsing(fn () => 'Menunggu'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($record) {
                        try {
                            app(PerformanceReviewService::class)->approve($record);
                            Notification::make()
                                ->title('Penilaian disetujui')
                                ->body("Penilaian untuk {$record->employee->name} telah disetujui.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyetujui')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('revise')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan Penilaian')
                    ->modalDescription('Penilaian akan dikembalikan ke supervisor untuk direvisi. Apakah Anda yakin?')
                    ->action(function ($record) {
                        app(PerformanceReviewService::class)->revise($record);
                        Notification::make()
                            ->title('Penilaian dikembalikan')
                            ->body("Penilaian untuk {$record->employee->name} dikembalikan ke supervisor.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->poll('10s')
            ->emptyStateHeading('Tidak ada penilaian menunggu approval')
            ->emptyStateDescription('Semua penilaian sudah diproses.');
    }

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd']);
    }
}