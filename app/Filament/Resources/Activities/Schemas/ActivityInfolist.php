<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity')
                    ->icon('heroicon-o-bolt')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('Member')->weight('bold'),
                        TextEntry::make('date')->date('M j, Y'),
                        TextEntry::make('type')->badge()->color('info'),
                        TextEntry::make('kcal')->label('Calories burned')->badge()->color('warning')->suffix(' kcal'),
                        TextEntry::make('mins')->label('Duration')->suffix(' min'),
                        TextEntry::make('distance')->placeholder('—'),
                    ]),
            ]);
    }
}
