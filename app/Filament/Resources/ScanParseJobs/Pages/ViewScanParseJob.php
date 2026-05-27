<?php

namespace App\Filament\Resources\ScanParseJobs\Pages;

use App\Filament\Resources\ScanParseJobs\ScanParseJobResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewScanParseJob extends ViewRecord
{
    protected static string $resource = ScanParseJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
