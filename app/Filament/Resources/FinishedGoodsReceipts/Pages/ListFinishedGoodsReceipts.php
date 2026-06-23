<?php

namespace App\Filament\Resources\FinishedGoodsReceipts\Pages;

use App\Filament\Resources\FinishedGoodsReceipts\FinishedGoodsReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinishedGoodsReceipts extends ListRecords
{
    protected static string $resource = FinishedGoodsReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
