<?php

namespace App\Filament\Resources\Dispatches\Pages;

use App\Filament\Resources\Dispatches\DispatchResource;
use App\Models\Dispatch;
use Filament\Resources\Pages\CreateRecord;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Dispatch::validateStockForItems($data['items'] ?? []);

        return $data;
    }
}
