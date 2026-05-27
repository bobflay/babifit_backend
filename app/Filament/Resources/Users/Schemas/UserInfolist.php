<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(3)
                    ->schema([
                        ImageEntry::make('avatar_url')
                            ->label('')
                            ->circular()
                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=10b981&color=fff'),
                        TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('email')
                            ->label('Email address')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                    ]),

                Section::make('Profile')
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('gender')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'M' => 'Male', 'F' => 'Female', 'O' => 'Other', default => '—',
                            })
                            ->placeholder('—'),
                        TextEntry::make('age')->suffix(' years')->placeholder('—'),
                        TextEntry::make('height')->suffix(' cm')->placeholder('—'),
                        TextEntry::make('initials')->placeholder('—'),
                        TextEntry::make('streak_days')
                            ->label('Streak')
                            ->badge()
                            ->icon('heroicon-m-fire')
                            ->color('warning')
                            ->suffix(' days'),
                        TextEntry::make('email_verified_at')
                            ->dateTime()
                            ->placeholder('Not verified'),
                    ]),

                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ]),
            ]);
    }
}
