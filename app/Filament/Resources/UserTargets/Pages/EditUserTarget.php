<?php

namespace App\Filament\Resources\UserTargets\Pages;

use App\Filament\Resources\UserTargets\UserTargetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserTarget extends EditRecord
{
    protected static string $resource = UserTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
