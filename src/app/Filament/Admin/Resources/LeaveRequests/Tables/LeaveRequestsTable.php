<?php

namespace App\Filament\Admin\Resources\LeaveRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\LeaveApprovalService;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\LeaveRequest;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->searchable(),
                TextColumn::make('leaveType.name')
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
                    ->badge(),
                TextColumn::make('supervisor.name')
                    ->searchable(),
                TextColumn::make('supervisor_approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hrd.name')
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
            ])
            ->filters([
                //
            ])
            ->recordActions([

    ViewAction::make(),

    EditAction::make()
    
        ->visible(fn (LeaveRequest $record) =>
            $record->status === 'pending'
            && $record->employee_id === auth()->user()->employee?->id
        ),

    Action::make('approveSupervisor')

    ->label('Approve')

    ->icon('heroicon-o-check')

    ->color('success')

    ->requiresConfirmation()

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

            ->send();

    }),

    Action::make('rejectSupervisor')

    ->label('Reject')

    ->icon('heroicon-o-x-mark')

    ->color('danger')

    ->requiresConfirmation()

    ->form([

        \Filament\Forms\Components\Textarea::make('note')

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

            ->send();

    }),

])

        

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
