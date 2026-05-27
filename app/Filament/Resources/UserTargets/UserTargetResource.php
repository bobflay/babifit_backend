<?php

namespace App\Filament\Resources\UserTargets;

use App\Filament\Resources\UserTargets\Pages\CreateUserTarget;
use App\Filament\Resources\UserTargets\Pages\EditUserTarget;
use App\Filament\Resources\UserTargets\Pages\ListUserTargets;
use App\Filament\Resources\UserTargets\Pages\ViewUserTarget;
use App\Filament\Resources\UserTargets\Schemas\UserTargetForm;
use App\Filament\Resources\UserTargets\Schemas\UserTargetInfolist;
use App\Filament\Resources\UserTargets\Tables\UserTargetsTable;
use App\Models\UserTarget;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class UserTargetResource extends Resource
{
    protected static ?string $model = UserTarget::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Members';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Targets';

    protected static ?string $modelLabel = 'target';

    public static function form(Schema $schema): Schema
    {
        return UserTargetForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserTargetInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserTargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserTargets::route('/'),
            'create' => CreateUserTarget::route('/create'),
            'view' => ViewUserTarget::route('/{record}'),
            'edit' => EditUserTarget::route('/{record}/edit'),
        ];
    }
}
