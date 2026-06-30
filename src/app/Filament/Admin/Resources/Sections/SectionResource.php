<?php

namespace App\Filament\Admin\Resources\Sections;

use App\Filament\Admin\Resources\Sections\Pages\CreateSection;
use App\Filament\Admin\Resources\Sections\Pages\EditSection;
use App\Filament\Admin\Resources\Sections\Pages\ListSections;
use App\Filament\Admin\Resources\Sections\Pages\ViewSection;
use App\Filament\Admin\Resources\Sections\Schemas\SectionForm;
use App\Filament\Admin\Resources\Sections\Schemas\SectionInfolist;
use App\Filament\Admin\Resources\Sections\Tables\SectionsTable;
use App\Models\Section;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SectionResource extends Resource
{
    protected static ?string $model = Section::class;
    protected static ?string $navigationLabel = 'Section';
    protected static ?string $pluralModelLabel = 'Data Section';
    protected static string|UnitEnum|null $navigationGroup = 'Struktur Perusahaan';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    public static function form(Schema $schema): Schema
    {
        return SectionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SectionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionsTable::configure($table);
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
            'index' => ListSections::route('/'),
            'create' => CreateSection::route('/create'),
            'view' => ViewSection::route('/{record}'),
            'edit' => EditSection::route('/{record}/edit'),
        ];
    }
}
