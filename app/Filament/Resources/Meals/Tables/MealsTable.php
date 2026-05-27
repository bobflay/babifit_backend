<?php

namespace App\Filament\Resources\Meals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MealsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->square()
                    ->getStateUsing(fn ($record): ?string => $record->photo?->url())
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=%F0%9F%8D%BD&background=e2e8f0&color=64748b'),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record): ?string => $record->time),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable()->toggleable(),
                TextColumn::make('date')->date('M j, Y')->sortable(),
                TextColumn::make('kcal')->label('Calories')->badge()->color('success')->suffix(' kcal')->sortable(),
                TextColumn::make('protein')->suffix(' g')->sortable()->toggleable(),
                TextColumn::make('carbs')->suffix(' g')->sortable()->toggleable(),
                TextColumn::make('fat')->suffix(' g')->sortable()->toggleable(),
            ])
            ->defaultSort('date', 'desc')
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
