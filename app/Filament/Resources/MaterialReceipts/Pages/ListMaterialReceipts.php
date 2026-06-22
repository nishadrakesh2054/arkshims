<?php

namespace App\Filament\Resources\MaterialReceipts\Pages;

use App\Filament\Resources\MaterialReceipts\MaterialReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaterialReceipts extends ListRecords
{
    protected static string $resource = MaterialReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
