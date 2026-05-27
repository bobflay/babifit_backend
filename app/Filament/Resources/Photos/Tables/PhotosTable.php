<?php

namespace App\Filament\Resources\Photos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhotosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('preview')
                    ->label('')
                    ->square()
                    ->getStateUsing(fn ($record): ?string => $record->url()),
                TextColumn::make('id')->label('ID')->searchable()->copyable()->fontFamily('mono'),
                TextColumn::make('kind')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'scan' ? 'info' : 'success'),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('mime')->placeholder('—')->toggleable(),
                TextColumn::make('size')
                    ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1).' KB' : '—')
                    ->sortable(),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('kind')->options([
                    'dish' => 'Dish',
                    'scan' => 'Scan',
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
