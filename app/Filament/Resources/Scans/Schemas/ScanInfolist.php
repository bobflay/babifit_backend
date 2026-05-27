<?php

namespace App\Filament\Resources\Scans\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scan')
                    ->icon('heroicon-o-scale')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('Member')->weight('bold'),
                        TextEntry::make('date')->date('M j, Y'),
                        TextEntry::make('label')->placeholder('—'),
                    ]),

                Section::make('Composition')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('weight')->suffix(' kg'),
                        TextEntry::make('muscle')->suffix(' kg')->color('success'),
                        TextEntry::make('fat')->label('Fat mass')->suffix(' kg'),
                        TextEntry::make('fat_pct')->label('Body fat')->suffix('%')->color('danger'),
                        TextEntry::make('bmi')->label('BMI'),
                        TextEntry::make('health')->label('Health score')->badge()->color('success'),
                    ]),

                Section::make('Detail metrics')
                    ->icon('heroicon-o-beaker')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('bmr')->label('BMR')->suffix(' kcal')->placeholder('—'),
                        TextEntry::make('water')->suffix(' L')->placeholder('—'),
                        TextEntry::make('visceral')->placeholder('—'),
                        TextEntry::make('waist_hip')->label('Waist/Hip')->placeholder('—'),
                        TextEntry::make('protein')->suffix(' kg')->placeholder('—'),
                        TextEntry::make('salt')->suffix(' g')->placeholder('—'),
                        TextEntry::make('lean_mass')->suffix(' kg')->placeholder('—'),
                        TextEntry::make('intra_water')->label('Intracellular water')->placeholder('—'),
                        TextEntry::make('extra_water')->label('Extracellular water')->placeholder('—'),
                        TextEntry::make('abdominal_fat')->placeholder('—'),
                        TextEntry::make('subcut_fat')->label('Subcutaneous fat')->placeholder('—'),
                        TextEntry::make('burn_rec')->label('Recommended burn')->suffix(' kcal')->placeholder('—'),
                    ]),

                Section::make('Structured data')
                    ->icon('heroicon-o-code-bracket')
                    ->collapsed()
                    ->schema([
                        KeyValueEntry::make('nutrition')->keyLabel('Nutrient')->valueLabel('Level')->placeholder('—'),
                        TextEntry::make('ranges')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->fontFamily('mono'),
                        TextEntry::make('segments')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->fontFamily('mono'),
                    ]),
            ]);
    }
}
