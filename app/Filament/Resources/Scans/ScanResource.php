<?php

namespace App\Filament\Resources\Scans;

use App\Filament\Resources\Scans\Pages\CreateScan;
use App\Filament\Resources\Scans\Pages\EditScan;
use App\Filament\Resources\Scans\Pages\ListScans;
use App\Filament\Resources\Scans\Pages\ViewScan;
use App\Filament\Resources\Scans\Schemas\ScanForm;
use App\Filament\Resources\Scans\Schemas\ScanInfolist;
use App\Filament\Resources\Scans\Tables\ScansTable;
use App\Models\Scan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ScanResource extends Resource
{
    protected static ?string $model = Scan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static string|\UnitEnum|null $navigationGroup = 'Tracking';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Schema $schema): Schema
    {
        return ScanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScans::route('/'),
            'create' => CreateScan::route('/create'),
            'view' => ViewScan::route('/{record}'),
            'edit' => EditScan::route('/{record}/edit'),
        ];
    }
}
