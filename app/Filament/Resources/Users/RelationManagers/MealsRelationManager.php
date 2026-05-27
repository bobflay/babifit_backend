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

class MealsRelationManager extends RelationManager
{
    protected static string $relationship = 'meals';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-cake';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
                DatePicker::make('date')->required(),
                TextInput::make('time')->placeholder('08:30')->maxLength(5),
                TextInput::make('kcal')->numeric()->default(0)->suffix('kcal'),
                TextInput::make('protein')->numeric()->default(0)->suffix('g'),
                TextInput::make('carbs')->numeric()->default(0)->suffix('g'),
                TextInput::make('fat')->numeric()->default(0)->suffix('g'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('M j')->sortable(),
                TextColumn::make('time')->placeholder('—'),
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('kcal')->badge()->color('success')->suffix(' kcal')->sortable(),
                TextColumn::make('protein')->suffix(' g')->toggleable(),
                TextColumn::make('carbs')->suffix(' g')->toggleable(),
                TextColumn::make('fat')->suffix(' g')->toggleable(),
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
