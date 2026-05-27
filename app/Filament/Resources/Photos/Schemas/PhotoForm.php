<?php

namespace App\Filament\Resources\Photos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PhotoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Photo')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('kind')
                            ->options([
                                'dish' => 'Dish',
                                'scan' => 'Scan',
                            ])
                            ->default('dish')
                            ->native(false)
                            ->required(),
                        FileUpload::make('path')
                            ->label('File')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('uploads')
                            ->visibility('public')
                            ->columnSpanFull()
                            ->helperText('Stored on the public disk; the value saved is the relative path.'),
                    ]),

                Section::make('Storage metadata')
                    ->icon('heroicon-o-server-stack')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        TextInput::make('disk')->default('public'),
                        TextInput::make('mime')->placeholder('image/jpeg'),
                        TextInput::make('size')->numeric()->suffix('bytes'),
                    ]),
            ]);
    }
}
