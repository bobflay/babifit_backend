<?php

namespace App\Filament\Resources\Scans\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scan')
                    ->icon('heroicon-o-scale')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('photo_id')
                            ->label('Source report')
                            ->relationship('photo', 'id')
                            ->searchable()
                            ->preload()
                            ->placeholder('No source photo'),
                        DatePicker::make('date')->required(),
                        TextInput::make('label')->placeholder('Derived from date, e.g. "26 May"'),
                    ]),

                Section::make('Composition')
                    ->description('Headline body-composition figures.')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        TextInput::make('weight')->numeric()->required()->suffix('kg'),
                        TextInput::make('muscle')->numeric()->required()->suffix('kg'),
                        TextInput::make('fat')->label('Fat mass')->numeric()->required()->suffix('kg'),
                        TextInput::make('fat_pct')->label('Body fat')->numeric()->required()->suffix('%'),
                        TextInput::make('bmi')->label('BMI')->numeric()->required(),
                        TextInput::make('health')->label('Health score')->numeric()->required(),
                    ]),

                Section::make('Detail metrics')
                    ->icon('heroicon-o-beaker')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextInput::make('bmr')->label('BMR')->numeric()->suffix('kcal'),
                        TextInput::make('water')->numeric()->suffix('L'),
                        TextInput::make('visceral')->numeric(),
                        TextInput::make('waist_hip')->label('Waist/Hip')->numeric(),
                        TextInput::make('protein')->numeric()->suffix('kg'),
                        TextInput::make('salt')->numeric()->suffix('g'),
                        TextInput::make('lean_mass')->numeric()->suffix('kg'),
                        TextInput::make('intra_water')->label('Intracellular water')->numeric(),
                        TextInput::make('extra_water')->label('Extracellular water')->numeric(),
                        TextInput::make('abdominal_fat')->numeric(),
                        TextInput::make('subcut_fat')->label('Subcutaneous fat')->numeric(),
                        TextInput::make('burn_rec')->label('Recommended burn')->numeric()->suffix('kcal'),
                    ]),

                Section::make('Structured data')
                    ->description('Nested values rendered by the scan-detail screen.')
                    ->icon('heroicon-o-code-bracket')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('nutrition')
                            ->keyLabel('Nutrient')
                            ->valueLabel('Level')
                            ->helperText('Flat map, e.g. protein → low.'),
                        Textarea::make('ranges')
                            ->rows(5)
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? json_decode($state, true) : null)
                            ->rules(['nullable', 'json'])
                            ->helperText('Raw JSON: { "weight": [min, low, high, max], … }'),
                        Textarea::make('segments')
                            ->rows(5)
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? json_decode($state, true) : null)
                            ->rules(['nullable', 'json'])
                            ->helperText('Raw JSON: { "armR": { "muscle": .., "fat": .. }, … }'),
                    ]),
            ]);
    }
}
