<?php

namespace App\Filament\Admin\Resources\LeaveRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LeaveRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('leaveType.name')
                    ->label('Leave type'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('total_days')
                    ->numeric(),
                TextEntry::make('reason')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('supervisor.name')
                    ->label('Supervisor')
                    ->placeholder('-'),
                TextEntry::make('supervisor_approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('supervisor_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('hrd.name')
                    ->label('Hrd')
                    ->placeholder('-'),
                TextEntry::make('hrd_approved_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('hrd_note')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
