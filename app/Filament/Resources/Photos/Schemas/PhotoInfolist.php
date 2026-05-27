<?php

namespace App\Filament\Resources\Photos\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PhotoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        ImageEntry::make('preview')
                            ->label('')
                            ->getStateUsing(fn ($record): ?string => $record->url())
                            ->placeholder('No file'),
                    ]),
                Section::make('Details')
                    ->icon('heroicon-o-photo')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('ID')->copyable()->fontFamily('mono'),
                        TextEntry::make('kind')->badge()->color(fn (string $state): string => $state === 'scan' ? 'info' : 'success'),
                        TextEntry::make('user.name')->label('Member'),
                        TextEntry::make('disk'),
                        TextEntry::make('mime')->placeholder('—'),
                        TextEntry::make('size')->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—'),
                        TextEntry::make('path')->columnSpanFull()->copyable(),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
            ]);
    }
}
