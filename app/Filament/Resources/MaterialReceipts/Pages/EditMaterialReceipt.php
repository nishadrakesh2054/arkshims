<?php

namespace App\Filament\Resources\MaterialReceipts\Pages;

use App\Filament\Resources\MaterialReceipts\MaterialReceiptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaterialReceipt extends EditRecord
{
    protected static string $resource = MaterialReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
