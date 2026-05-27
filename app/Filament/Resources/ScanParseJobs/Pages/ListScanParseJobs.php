<?php

namespace App\Filament\Resources\ScanParseJobs\Pages;

use App\Filament\Resources\ScanParseJobs\ScanParseJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListScanParseJobs extends ListRecords
{
    protected static string $resource = ScanParseJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
