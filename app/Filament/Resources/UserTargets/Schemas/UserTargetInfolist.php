<?php

namespace App\Filament\Resources\UserTargets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserTargetInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Daily intake targets')
                    ->icon('heroicon-o-fire')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('Member')->weight('bold'),
                        TextEntry::make('calories')->badge()->color('success')->suffix(' kcal'),
                        TextEntry::make('burn')->badge()->color('warning')->suffix(' kcal'),
                        TextEntry::make('protein')->suffix(' g'),
                        TextEntry::make('carbs')->suffix(' g'),
                        TextEntry::make('fat')->suffix(' g'),
                    ]),
                Section::make('Body goals')
                    ->icon('heroicon-o-trophy')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('weight')->label('Goal weight')->suffix(' kg')->placeholder('—'),
                        TextEntry::make('fat_pct')->label('Goal body fat')->suffix('%')->placeholder('—'),
                    ]),
            ]);
    }
}
