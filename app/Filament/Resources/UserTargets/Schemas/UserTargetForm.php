<?php

namespace App\Filament\Resources\UserTargets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserTargetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Member')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Each member has a single set of targets.'),
                    ]),

                Section::make('Daily intake targets')
                    ->icon('heroicon-o-fire')
                    ->columns(3)
                    ->schema([
                        TextInput::make('calories')->numeric()->required()->default(0)->suffix('kcal'),
                        TextInput::make('protein')->numeric()->required()->default(0)->suffix('g'),
                        TextInput::make('carbs')->numeric()->required()->default(0)->suffix('g'),
                        TextInput::make('fat')->numeric()->required()->default(0)->suffix('g'),
                        TextInput::make('burn')->label('Burn target')->numeric()->required()->default(0)->suffix('kcal'),
                    ]),

                Section::make('Body goals')
                    ->icon('heroicon-o-trophy')
                    ->columns(2)
                    ->schema([
                        TextInput::make('weight')->label('Goal weight')->numeric()->suffix('kg'),
                        TextInput::make('fat_pct')->label('Goal body fat')->numeric()->suffix('%'),
                    ]),
            ]);
    }
}
