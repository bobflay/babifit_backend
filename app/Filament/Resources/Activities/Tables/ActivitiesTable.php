<?php

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->date('M j, Y')->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('type')->badge()->color('info')->icon('heroicon-m-bolt')->searchable(),
                TextColumn::make('kcal')->label('Burned')->badge()->color('warning')->suffix(' kcal')->sortable(),
                TextColumn::make('mins')->label('Duration')->suffix(' min')->sortable(),
                TextColumn::make('distance')->placeholder('—'),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options(fn (): array => \App\Models\Activity::query()->distinct()->orderBy('type')->pluck('type', 'type')->all()),
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
