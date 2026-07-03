<?php

namespace App\Filament\Admin\Resources\LeaveRequests;

use App\Filament\Admin\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Admin\Resources\LeaveRequests\Pages\ViewLeaveRequest;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Admin\Resources\LeaveRequests\Schemas\LeaveRequestInfolist;
use App\Filament\Admin\Resources\LeaveRequests\Tables\LeaveRequestsTable;
// use App\Filament\Admin\Resources\LeaveRequests\RelationManagers\DocumentsRelationManager;
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
    protected static ?int $navigationSort = 1;
    
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationLabel = 'Permintaan Cuti';

    protected static ?string $modelLabel = 'Permintaan Cuti';

    protected static ?string $pluralModelLabel = 'Permintaan Cuti';

    protected static string|UnitEnum|null $navigationGroup = 'Management Cuti';

    protected static string|BackedEnum|null $navigationIcon =Heroicon::OutlinedCalendarDays;

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

    $query = parent::getEloquentQuery();

    // Super Admin & HRD melihat semua
    if (
        $user->hasRole('super_admin') ||
        $user->hasRole('hrd')
    ) {
        return $query;
    }

    $employee = $user->employee;

    if (! $employee) {
        return $query->whereRaw('1 = 0');
    }

    /*
    |--------------------------------------------------------------------------
    | Kepala Bagian
    |--------------------------------------------------------------------------
    */

    if ($user->hasRole('kepala_bagian')) {

        $ids = $employee
            ->subordinates()
            ->pluck('id')
            ->push($employee->id);

        return $query->whereIn('employee_id', $ids);
    }

    /*
    |--------------------------------------------------------------------------
    | Semua selain HRD & Kepala Bagian
    |--------------------------------------------------------------------------
    */

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
