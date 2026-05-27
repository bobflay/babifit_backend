<?php

namespace App\Filament\Resources\ScanParseJobs\Pages;

use App\Filament\Resources\ScanParseJobs\ScanParseJobResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditScanParseJob extends EditRecord
{
    protected static string $resource = ScanParseJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
