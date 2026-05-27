<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->description('Basic identity shown across the app header.')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('gender')
                            ->options([
                                'M' => 'Male',
                                'F' => 'Female',
                                'O' => 'Other',
                            ])
                            ->native(false),
                        TextInput::make('initials')
                            ->maxLength(4)
                            ->placeholder('Auto-derived from name')
                            ->helperText('Leave blank to derive from the name.'),
                        TextInput::make('age')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(120)
                            ->suffix('years'),
                        TextInput::make('height')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(300)
                            ->suffix('cm'),
                    ]),

                Section::make('Account & status')
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Leave blank to keep the current password.'),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verified at'),
                        TextInput::make('streak_days')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required()
                            ->suffix('days'),
                        TextInput::make('avatar_url')
                            ->label('Avatar URL')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
