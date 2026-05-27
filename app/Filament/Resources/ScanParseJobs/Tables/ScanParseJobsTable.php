<?php

namespace App\Filament\Resources\ScanParseJobs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScanParseJobsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->searchable()->copyable()->fontFamily('mono'),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->icon(fn (string $state): string => match ($state) {
                        'processing' => 'heroicon-m-arrow-path',
                        'done' => 'heroicon-m-check-circle',
                        'failed' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'processing' => 'warning',
                        'done' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('confidence')
                    ->formatStateUsing(fn (?float $state): string => $state !== null ? round($state * 100).'%' : '—')
                    ->sortable(),
                TextColumn::make('photo.id')->label('Photo')->placeholder('—')->fontFamily('mono')->toggleable(),
                TextColumn::make('created_at')->dateTime('M j, Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'processing' => 'Processing',
                    'done' => 'Done',
                    'failed' => 'Failed',
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
