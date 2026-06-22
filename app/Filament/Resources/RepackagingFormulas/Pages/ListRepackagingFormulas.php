<?php

namespace App\Filament\Resources\RepackagingFormulas\Pages;

use App\Filament\Resources\RepackagingFormulas\RepackagingFormulaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepackagingFormulas extends ListRecords
{
    protected static string $resource = RepackagingFormulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
