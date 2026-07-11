<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests\Pages;

use App\Filament\Admin\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

final class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        abort_unless(
            in_array($this->record->status, [
                'pending',
                'supervisor_approved',
            ]),
            403
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
