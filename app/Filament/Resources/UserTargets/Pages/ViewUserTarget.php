<?php

namespace App\Filament\Resources\UserTargets\Pages;

use App\Filament\Resources\UserTargets\UserTargetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserTarget extends ViewRecord
{
    protected static string $resource = UserTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
