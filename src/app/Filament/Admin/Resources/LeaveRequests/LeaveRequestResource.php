<?php

namespace App\Filament\Admin\Resources\LeaveRequests;

use App\Filament\Admin\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ViewLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestInfolist;
use App\Filament\Admin\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Filament\Admin\Resources\LeaveRequests\RelationManagers\DocumentsRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Models\LeaveRequest;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationLabel = 'Permintaan Cuti';

    protected static ?string $modelLabel = 'Permintaan Cuti';

    protected static ?string $pluralModelLabel = 'Permintaan Cuti';

    protected static string|UnitEnum|null $navigationGroup = 'Management Cuti';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-s-chat-bubble-left-right';

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
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole('super_admin') || $user->hasRole('hrd')) {
            return $query;
        }

        if ($user->hasRole('kepala_bagian')) {
            // Kepala bagian melihat cuti bawahannya + dirinya sendiri
            $employee = $user->employee;
            if ($employee) {
                $subordinateIds = $employee->subordinates()->pluck('id');
                return $query->whereIn('employee_id', $subordinateIds->push($employee->id));
            }
            return $query->whereRaw('0=1');
        }

        // Karyawan biasa: hanya melihat punya sendiri
        if ($user->hasRole('karyawan')) {
            return $query->where('employee_id', $user->employee?->id ?? 0);
        }

        return $query->whereRaw('0=1');
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
