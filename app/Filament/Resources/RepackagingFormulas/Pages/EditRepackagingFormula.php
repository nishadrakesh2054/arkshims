<?php

namespace App\Filament\Resources\RepackagingFormulas\Pages;

use App\Filament\Resources\RepackagingFormulas\RepackagingFormulaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRepackagingFormula extends EditRecord
{
    protected static string $resource = RepackagingFormulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
