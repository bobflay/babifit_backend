<?php

namespace App\Filament\Resources\ScanParseJobs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScanParseJobInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Job')
                    ->icon('heroicon-o-cpu-chip')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('ID')->copyable()->fontFamily('mono'),
                        TextEntry::make('user.name')->label('Member'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'processing' => 'warning',
                                'done' => 'success',
                                'failed' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('confidence')->formatStateUsing(fn (?float $state): string => $state !== null ? round($state * 100).'%' : '—'),
                        TextEntry::make('photo.id')->label('Source photo')->placeholder('—')->fontFamily('mono'),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Result')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextEntry::make('insight')->placeholder('—')->columnSpanFull(),
                        TextEntry::make('draft')
                            ->label('Draft (JSON)')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($state): ?string => filled($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null)
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
