<?php

namespace App\Filament\Resources\Scans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date('M j, Y')->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('label')->placeholder('—')->toggleable(),
                TextColumn::make('weight')->numeric(1)->suffix(' kg')->sortable(),
                TextColumn::make('fat_pct')->label('Body fat')->numeric(1)->suffix('%')->sortable()->color('danger'),
                TextColumn::make('muscle')->numeric(1)->suffix(' kg')->sortable()->color('success'),
                TextColumn::make('bmi')->label('BMI')->numeric(1)->sortable(),
                TextColumn::make('health')->label('Health')->badge()->color('success')->sortable(),
                TextColumn::make('bmr')->label('BMR')->numeric(0)->suffix(' kcal')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visceral')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
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
