<?php

namespace App\Filament\Widgets;

use App\Models\InventoryTransaction;
use App\Models\MaterialReceipt;
use App\Models\RawMaterial;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Raw Materials', RawMaterial::query()->count())
                ->description('Total materials in inventory')
                ->descriptionIcon(Heroicon::OutlinedCube)
                ->color('primary'),
            Stat::make('Stock Entries', MaterialReceipt::query()->count())
                ->description('Total material receipts')
                ->descriptionIcon(Heroicon::OutlinedArchiveBox)
                ->color('success'),
            Stat::make('Stock Value', '—')
                ->description('Coming soon')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('gray'),
            Stat::make('Low Stock Items', RawMaterial::countLowStock())
                ->description('At or below minimum stock')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }
}
