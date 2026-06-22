<?php

namespace App\Filament\Resources\RepackagingBatches\Pages;

use App\Filament\Resources\RepackagingBatches\RepackagingBatchResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepackagingBatch extends EditRecord
{
    protected static string $resource = RepackagingBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
