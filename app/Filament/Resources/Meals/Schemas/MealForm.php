<?php

namespace App\Filament\Resources\Meals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MealForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meal')
                    ->icon('heroicon-o-cake')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('photo_id')
                            ->label('Dish photo')
                            ->relationship('photo', 'id')
                            ->searchable()
                            ->preload()
                            ->placeholder('No photo'),
                        DatePicker::make('date')->required(),
                        TextInput::make('time')->placeholder('08:30')->maxLength(5)->helperText('HH:MM'),
                    ]),

                Section::make('Macros')
                    ->icon('heroicon-o-chart-pie')
                    ->columns(4)
                    ->schema([
                        TextInput::make('kcal')->label('Calories')->numeric()->required()->default(0)->suffix('kcal'),
                        TextInput::make('protein')->numeric()->required()->default(0)->suffix('g'),
                        TextInput::make('carbs')->numeric()->required()->default(0)->suffix('g'),
                        TextInput::make('fat')->numeric()->required()->default(0)->suffix('g'),
                    ]),
            ]);
    }
}
