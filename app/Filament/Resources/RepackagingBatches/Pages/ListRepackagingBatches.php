<?php

namespace App\Filament\Resources\RepackagingBatches\Pages;

use App\Filament\Resources\RepackagingBatches\RepackagingBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepackagingBatches extends ListRecords
{
    protected static string $resource = RepackagingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
