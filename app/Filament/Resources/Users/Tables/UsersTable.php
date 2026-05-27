<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=10b981&color=fff'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): string => $record->email),
                TextColumn::make('gender')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'M' => 'Male',
                        'F' => 'Female',
                        'O' => 'Other',
                        default => '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'M' => 'info',
                        'F' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('age')
                    ->numeric()
                    ->suffix(' yr')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('height')
                    ->numeric()
                    ->suffix(' cm')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('streak_days')
                    ->label('Streak')
                    ->badge()
                    ->icon('heroicon-m-fire')
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('scans_count')
                    ->label('Scans')
                    ->counts('scans')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('meals_count')
                    ->label('Meals')
                    ->counts('meals')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gender')
                    ->options([
                        'M' => 'Male',
                        'F' => 'Female',
                        'O' => 'Other',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
