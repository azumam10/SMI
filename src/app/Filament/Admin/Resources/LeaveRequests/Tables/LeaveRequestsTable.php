<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests\Tables;

use App\Models\LeaveRequest;
use App\Services\LeaveApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

// ✅ Import Builder yang benar (tapi kita akan gunakan tanpa type hint untuk aman)
// use Illuminate\Database\Eloquent\Builder; // tidak wajib

final class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('leaveType.name')
                    ->label('Jenis Cuti')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'warning',
                        'supervisor_approved' => 'info',
                        'hrd_approved' => 'success',
                        'supervisor_rejected' => 'danger',
                        'hrd_rejected' => 'danger',
                        'cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Menunggu',
                        'supervisor_approved' => 'Disetujui Kepala Bagian',
                        'supervisor_rejected' => 'Ditolak Kepala Bagian',
                        'hrd_approved' => 'Disetujui HRD',
                        'hrd_rejected' => 'Ditolak HRD',
                        'cancelled' => 'Dibatalkan',
                    }),
                TextColumn::make('supervisor.name')
                    ->label('Kepala Bagian')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('supervisor_approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hrd.name')
                    ->label('HRD')
                    ->searchable(),
                TextColumn::make('hrd_approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('documents')
                    ->label('Dokumen')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->documents->isNotEmpty()),
            ])

            // ✅ Filter – tanpa type hint untuk menghindari konflik namespace
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu',
                        'supervisor_approved' => 'Disetujui Kepala Bagian',
                        'supervisor_rejected' => 'Ditolak Kepala Bagian',
                        'hrd_approved' => 'Disetujui HRD',
                        'hrd_rejected' => 'Ditolak HRD',
                        'cancelled' => 'Dibatalkan',
                    ]),

                SelectFilter::make('leave_type_id')
                    ->label('Jenis Cuti')
                    ->relationship('leaveType', 'name'),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->relationship('employee.department', 'name')
                    ->indicateUsing(fn ($data) => isset($data['value']) ? 'Departemen: '.$data['value'] : null),

                SelectFilter::make('supervisor_id')
                    ->label('Kepala Bagian')
                    ->relationship('supervisor', 'name')
                    ->indicateUsing(fn ($data) => isset($data['value']) ? 'Kepala Bagian: '.$data['value'] : null),

                // ✅ Filter tanggal – hilangkan type hint untuk menghindari error
                Filter::make('submitted_at_range')
                    ->form([
                        DatePicker::make('submitted_from')
                            ->label('Diajukan dari'),
                        DatePicker::make('submitted_until')
                            ->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date)
                            )
                            ->when(
                                $data['submitted_until'],
                                fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if (! empty($data['submitted_from'])) {
                            $indicators[] = 'Dari: '.$data['submitted_from'];
                        }
                        if (! empty($data['submitted_until'])) {
                            $indicators[] = 'Sampai: '.$data['submitted_until'];
                        }

                        return count($indicators) ? implode(' ', $indicators) : null;
                    }),
            ])

            // ✅ Default sort
            ->defaultSort('submitted_at', 'desc')

            ->recordActions([

                ViewAction::make(),

                EditAction::make()
                    ->visible(fn (LeaveRequest $record) => $record->status === 'pending'
                        && $record->employee_id === auth()->user()->employee?->id
                        && ! in_array($record->status, ['cancelled', 'hrd_approved', 'hrd_rejected'])
                    ),

                // ----- Kepala Bagian -----
                Action::make('approveSupervisor')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Cuti')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->visible(function (LeaveRequest $record) {
                        return auth()->user()->hasRole('kepala_bagian')
                            && $record->status === 'pending'
                            && $record->supervisor_id === auth()->user()->employee?->id;
                    })
                    ->action(function (LeaveRequest $record) {
                        app(LeaveApprovalService::class)
                            ->approveSupervisor(
                                $record,
                                auth()->user()->employee->id
                            );
                        Notification::make()
                            ->success()
                            ->title('Pengajuan cuti disetujui.')
                            ->duration(3000)
                            ->send();
                    }),

                Action::make('rejectSupervisor')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalWidth('xl') // ✅ pakai string, bukan enum
                    ->form([
                        Textarea::make('note')
                            ->required()
                            ->label('Catatan'),
                    ])
                    ->visible(function (LeaveRequest $record) {
                        return auth()->user()->hasRole('kepala_bagian')
                            && $record->status === 'pending'
                            && $record->supervisor_id === auth()->user()->employee?->id;
                    })
                    ->action(function (LeaveRequest $record, array $data) {
                        app(LeaveApprovalService::class)
                            ->rejectSupervisor(
                                $record,
                                auth()->user()->employee->id,
                                $data['note']
                            );
                        Notification::make()
                            ->success()
                            ->title('Pengajuan cuti ditolak.')
                            ->duration(3000)
                            ->send();
                    }),

                // ----- HRD -----
                Action::make('approveHrd')
                    ->label('Setujui HRD')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Pengajuan Cuti')
                    ->modalDescription('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->form([
                        Textarea::make('note')
                            ->label('Catatan HRD'),
                    ])
                    ->visible(function (LeaveRequest $record) {
                        if (! auth()->user()->hasRole('hrd')) {
                            return false;
                        }
                        if ($record->supervisor_id) {
                            return $record->status === 'supervisor_approved';
                        }

                        return $record->status === 'pending';
                    })
                    ->action(function (LeaveRequest $record, array $data) {
                        app(LeaveApprovalService::class)
                            ->approveHrd(
                                $record,
                                auth()->user()->employee->id,
                                $data['note'] ?? null
                            );
                        Notification::make()
                            ->success()
                            ->title('Cuti berhasil disetujui HRD')
                            ->duration(3000)
                            ->send();
                    }),

                Action::make('rejectHrd')
                    ->label('Tolak HRD')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalWidth('xl') // ✅ pakai string
                    ->form([
                        Textarea::make('note')
                            ->required()
                            ->label('Alasan Penolakan'),
                    ])
                    ->visible(function (LeaveRequest $record) {
                        if (! auth()->user()->hasRole('hrd')) {
                            return false;
                        }
                        if ($record->supervisor_id) {
                            return $record->status === 'supervisor_approved';
                        }

                        return $record->status === 'pending';
                    })
                    ->action(function (LeaveRequest $record, array $data) {
                        app(LeaveApprovalService::class)
                            ->rejectHrd(
                                $record,
                                auth()->user()->employee->id,
                                $data['note']
                            );
                        Notification::make()
                            ->success()
                            ->title('Pengajuan ditolak HRD')
                            ->duration(3000)
                            ->send();
                    }),

                // ----- Batalkan -----
                Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Pengajuan yang dibatalkan tidak dapat diproses kembali.')
                    ->visible(function (LeaveRequest $record) {
                        return
                            $record->employee_id === auth()->user()->employee?->id
                            &&
                            in_array($record->status, [
                                'pending',
                                'supervisor_approved',
                            ]);
                    })
                    ->action(function (LeaveRequest $record) {
                        app(LeaveApprovalService::class)
                            ->cancel(
                                $record,
                                auth()->id()
                            );
                        Notification::make()
                            ->success()
                            ->title('Pengajuan berhasil dibatalkan')
                            ->duration(3000)
                            ->send();
                    }),
            ])

            ->toolbarActions([]);
    }
}
