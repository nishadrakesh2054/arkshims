<?php

namespace App\Filament\Resources\MaterialReceipts\Pages;

use App\Filament\Resources\MaterialReceipts\MaterialReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterialReceipt extends CreateRecord
{
    protected static string $resource = MaterialReceiptResource::class;
}
