<?php

namespace App\Filament\Resources\Activities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Activity')
                    ->icon('heroicon-o-bolt')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('date')->required(),
                        TextInput::make('type')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Walk, Cycling, Jog…')
                            ->datalist(['Walk', 'Jog', 'Run', 'Cycling', 'Swim', 'Gym', 'Yoga', 'Hike']),
                        TextInput::make('distance')->placeholder('4.1 km')->maxLength(255),
                        TextInput::make('kcal')->label('Calories burned')->numeric()->required()->default(0)->suffix('kcal'),
                        TextInput::make('mins')->label('Duration')->numeric()->required()->default(0)->suffix('min'),
                    ]),
            ]);
    }
}
