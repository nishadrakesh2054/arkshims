<?php

namespace App\Filament\Resources\Dispatches\Pages;

use App\Filament\Resources\Dispatches\DispatchResource;
use App\Models\Dispatch;
use App\Support\InventoryGuard;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDispatch extends CreateRecord
{
    protected static string $resource = DispatchResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return InventoryGuard::transaction(function () use ($data): Model {
            Dispatch::validateStockForItems($data['items'] ?? []);

            return parent::handleRecordCreation($data);
        });
    }
}
