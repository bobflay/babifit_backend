<?php

namespace App\Filament\Resources\ScanParseJobs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScanParseJobForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job')
                    ->icon('heroicon-o-cpu-chip')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('photo_id')
                            ->label('Source photo')
                            ->relationship('photo', 'id')
                            ->searchable()
                            ->preload()
                            ->placeholder('No photo'),
                        Select::make('status')
                            ->options([
                                'processing' => 'Processing',
                                'done' => 'Done',
                                'failed' => 'Failed',
                            ])
                            ->default('processing')
                            ->native(false)
                            ->required(),
                        TextInput::make('confidence')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.01)
                            ->helperText('0.00 – 1.00'),
                    ]),

                Section::make('Result')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Textarea::make('insight')->rows(3)->columnSpanFull(),
                        Textarea::make('draft')
                            ->label('Draft (JSON)')
                            ->rows(8)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? json_decode($state, true) : null)
                            ->rules(['nullable', 'json'])
                            ->helperText('Unsaved scan draft (shape of GET /scans/{id}).'),
                    ]),
            ]);
    }
}
