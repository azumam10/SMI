<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveRequests;

use App\Filament\Admin\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ViewLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestInfolist;
use App\Filament\Admin\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class LeaveRequestResource extends Resource
{
    protected static ?int $navigationSort = 1;

    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationLabel = 'Permintaan Cuti';

    protected static ?string $modelLabel = 'Permintaan Cuti';

    protected static ?string $pluralModelLabel = 'Permintaan Cuti';

    protected static string|UnitEnum|null $navigationGroup = 'Management Cuti';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey([]);
        }

        $query = parent::getEloquentQuery()
            ->with([
                'employee',
                'leaveType',
                'supervisor',
                'hrd',
                'documents',
            ])
            ->withCount('documents');

        if (
            $user->hasRole('super_admin') ||
            $user->hasRole('hrd')
        ) {
            return $query;
        }

        $employee = $user->employee;

        if (! $employee) {
            return $query->whereKey([]);
        }

        if ($user->hasRole('kepala_bagian')) {

            $employeeIds = $employee
                ->subordinates()
                ->pluck('id')
                ->push($employee->id)
                ->unique()
                ->values();

            return $query->whereIn(
                'employee_id',
                $employeeIds
            );
        }

        return $query->where(
            'employee_id',
            $employee->id
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'view' => ViewLeaveRequest::route('/{record}'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
