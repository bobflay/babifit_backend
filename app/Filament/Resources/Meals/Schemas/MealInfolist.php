<?php

namespace App\Filament\Resources\Meals\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MealInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meal')
                    ->icon('heroicon-o-cake')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->size('lg')->weight('bold')->columnSpanFull(),
                        TextEntry::make('user.name')->label('Member'),
                        TextEntry::make('date')->date('M j, Y'),
                        TextEntry::make('time')->placeholder('—'),
                        ImageEntry::make('photo')
                            ->label('Dish photo')
                            ->getStateUsing(fn ($record): ?string => $record->photo?->url())
                            ->placeholder('No photo'),
                    ]),

                Section::make('Macros')
                    ->icon('heroicon-o-chart-pie')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('kcal')->label('Calories')->badge()->color('success')->suffix(' kcal'),
                        TextEntry::make('protein')->suffix(' g'),
                        TextEntry::make('carbs')->suffix(' g'),
                        TextEntry::make('fat')->suffix(' g'),
                    ]),
            ]);
    }
}
