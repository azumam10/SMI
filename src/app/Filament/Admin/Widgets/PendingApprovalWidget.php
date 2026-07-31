<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Actions\Action; 
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

final class PendingApprovalWidget extends TableWidget
{
    protected static ?string $heading = 'Persetujuan Penilaian Kinerja';

    protected ?string $description = 'Daftar evaluasi kinerja karyawan yang menunggu verifikasi dan persetujuan HRD';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '10s';

    public static function canView(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PerformanceReview::query()
                    ->where('status', PerformanceReview::STATUS_SUBMITTED)
                    ->with(['employee.department', 'reviewer']) 
                    ->orderBy('created_at', 'asc')
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
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary')
                    ->description(fn ($record) => $record->employee->department->name ?? 'Tanpa Departemen'),

                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Penilai / Supervisor')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user') // <-- DIBERSIHKAN: Menggunakan heroicon-m-user
                    ->iconColor('gray')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('semester_label')
                    ->label('Periode')
                    ->getStateUsing(fn ($record) => "Sem {$record->semester} ({$record->year})")
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Diajukan')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->alignCenter()
                    ->description(fn ($record) => $record->created_at?->diffForHumans()),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status Alert')
                    ->badge()
                    ->alignCenter()
                    ->color('warning')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(fn () => 'Menunggu Approval'),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Persetujuan')
                    ->modalDescription(fn ($record) => "Apakah Anda yakin ingin menyetujui penilaian kinerja untuk {$record->employee?->name}?")
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function ($record) {
                        try {
                            app(PerformanceReviewService::class)->approve($record);

                            Notification::make()
                                ->title('Penilaian Disetujui')
                                ->body("Penilaian kinerja untuk {$record->employee?->name} berhasil disetujui.")
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Gagal Menyetujui')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('revise')
                    ->label('Kembalikan')
                    ->button()
                    ->size('xs')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Kembalikan Penilaian')
                    ->modalDescription(fn ($record) => "Penilaian untuk {$record->employee?->name} akan dikembalikan ke penilai ({$record->reviewer?->name}) untuk direvisi. Lanjutkan?")
                    ->modalSubmitActionLabel('Ya, Kembalikan')
                    ->action(function ($record) {
                        try {
                            app(PerformanceReviewService::class)->revise($record);

                            Notification::make()
                                ->title('Penilaian Dikembalikan')
                                ->body("Penilaian untuk {$record->employee?->name} telah dikembalikan ke supervisor.")
                                ->warning()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('Gagal Mengembalikan')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->emptyStateHeading('Tidak Ada Antrean Approval')
            ->emptyStateDescription('Semua pengajuan penilaian kinerja telah selesai diproses.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}