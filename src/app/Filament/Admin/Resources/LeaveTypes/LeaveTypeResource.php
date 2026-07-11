<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\LeaveTypes;

use App\Filament\Admin\Resources\LeaveTypes\Pages\CreateLeaveType;
use App\Filament\Admin\Resources\LeaveTypes\Pages\EditLeaveType;
use App\Filament\Admin\Resources\LeaveTypes\Pages\ListLeaveTypes;
use App\Filament\Admin\Resources\LeaveTypes\Pages\ViewLeaveType;
use App\Filament\Admin\Resources\LeaveTypes\Schemas\LeaveTypeForm;
use App\Filament\Admin\Resources\LeaveTypes\Schemas\LeaveTypeInfolist;
use App\Filament\Admin\Resources\LeaveTypes\Tables\LeaveTypesTable;
use App\Models\LeaveType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

final class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationLabel = 'Jenis Cuti';

    protected static ?string $modelLabel = 'Jenis Cuti';

    protected static ?string $pluralModelLabel = 'Jenis Cuti';

    protected static string|UnitEnum|null $navigationGroup = 'Management Cuti';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    public static function form(Schema $schema): Schema
    {
        return LeaveTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeaveTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveTypes::route('/'),
            'create' => CreateLeaveType::route('/create'),
            'view' => ViewLeaveType::route('/{record}'),
            'edit' => EditLeaveType::route('/{record}/edit'),
        ];
    }
}
