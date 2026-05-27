<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScansRelationManager extends RelationManager
{
    protected static string $relationship = 'scans';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-scale';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('date')->required(),
                TextInput::make('label')->placeholder('Derived from date'),
                TextInput::make('weight')->numeric()->required()->suffix('kg'),
                TextInput::make('fat_pct')->label('Body fat')->numeric()->required()->suffix('%'),
                TextInput::make('muscle')->numeric()->required()->suffix('kg'),
                TextInput::make('bmi')->numeric()->required(),
                TextInput::make('health')->numeric()->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('M j, Y')->sortable(),
                TextColumn::make('label')->placeholder('—'),
                TextColumn::make('weight')->numeric(1)->suffix(' kg')->sortable(),
                TextColumn::make('fat_pct')->label('Fat %')->numeric(1)->suffix('%')->sortable(),
                TextColumn::make('muscle')->numeric(1)->suffix(' kg')->sortable(),
                TextColumn::make('health')->badge()->color('success')->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
