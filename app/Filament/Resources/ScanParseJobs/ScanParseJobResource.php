<?php

namespace App\Filament\Resources\ScanParseJobs;

use App\Filament\Resources\ScanParseJobs\Pages\CreateScanParseJob;
use App\Filament\Resources\ScanParseJobs\Pages\EditScanParseJob;
use App\Filament\Resources\ScanParseJobs\Pages\ListScanParseJobs;
use App\Filament\Resources\ScanParseJobs\Pages\ViewScanParseJob;
use App\Filament\Resources\ScanParseJobs\Schemas\ScanParseJobForm;
use App\Filament\Resources\ScanParseJobs\Schemas\ScanParseJobInfolist;
use App\Filament\Resources\ScanParseJobs\Tables\ScanParseJobsTable;
use App\Models\ScanParseJob;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ScanParseJobResource extends Resource
{
    protected static ?string $model = ScanParseJob::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static string|\UnitEnum|null $navigationGroup = 'Media & Jobs';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Scan parse jobs';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ScanParseJobForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ScanParseJobInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScanParseJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getNavigationBadge(): ?string
    {
        $processing = static::getModel()::where('status', 'processing')->count();

        return $processing > 0 ? (string) $processing : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScanParseJobs::route('/'),
            'create' => CreateScanParseJob::route('/create'),
            'view' => ViewScanParseJob::route('/{record}'),
            'edit' => EditScanParseJob::route('/{record}/edit'),
        ];
    }
}
