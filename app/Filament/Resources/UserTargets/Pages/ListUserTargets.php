<?php

namespace App\Filament\Resources\UserTargets\Pages;

use App\Filament\Resources\UserTargets\UserTargetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserTargets extends ListRecords
{
    protected static string $resource = UserTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
