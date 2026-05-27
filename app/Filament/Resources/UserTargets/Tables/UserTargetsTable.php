<?php

namespace App\Filament\Resources\UserTargets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserTargetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('calories')->badge()->color('success')->suffix(' kcal')->sortable(),
                TextColumn::make('protein')->suffix(' g')->sortable(),
                TextColumn::make('carbs')->suffix(' g')->sortable(),
                TextColumn::make('fat')->suffix(' g')->sortable(),
                TextColumn::make('burn')->badge()->color('warning')->suffix(' kcal')->sortable(),
                TextColumn::make('weight')->label('Goal kg')->numeric(1)->suffix(' kg')->placeholder('—')->sortable(),
                TextColumn::make('fat_pct')->label('Goal fat %')->numeric(1)->suffix('%')->placeholder('—')->sortable(),
            ])
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
