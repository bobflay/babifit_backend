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

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-bolt';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                DatePicker::make('date')->required(),
                TextInput::make('type')->required()->placeholder('Walk, Cycling, Jog…')->maxLength(255),
                TextInput::make('kcal')->numeric()->default(0)->suffix('kcal'),
                TextInput::make('mins')->numeric()->default(0)->suffix('min'),
                TextInput::make('distance')->placeholder('4.1 km')->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('M j')->sortable(),
                TextColumn::make('type')->badge()->color('info')->searchable(),
                TextColumn::make('kcal')->badge()->color('warning')->suffix(' kcal')->sortable(),
                TextColumn::make('mins')->suffix(' min')->sortable(),
                TextColumn::make('distance')->placeholder('—'),
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
