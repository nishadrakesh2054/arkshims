<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\RawMaterials\RawMaterialResource;
use App\Filament\Resources\Skus\SkuResource;
use App\Filament\Resources\StockAdjustments\StockAdjustmentResource;
use App\Models\RawMaterial;
use App\Models\Sku;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $fgTotal = Sku::query()
            ->withCurrentStock()
            ->get()
            ->sum(fn (Sku $sku): int => $sku->current_stock);

        $lowStock = RawMaterial::countLowStock() + Sku::countLowStock();

        return [
            Stat::make('Raw Materials', RawMaterial::query()->count())
                ->description('Active catalog items')
                ->descriptionIcon(Heroicon::OutlinedCube)
                ->color('primary')
                ->url(RawMaterialResource::getUrl()),
            Stat::make('Stock Value', '₹'.number_format(RawMaterial::totalStockValue(), 2))
                ->description('Raw inventory valuation')
                ->descriptionIcon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('FG On Hand', number_format($fgTotal))
                ->description('Finished goods units')
                ->descriptionIcon(Heroicon::OutlinedShoppingBag)
                ->color('info')
                ->url(SkuResource::getUrl()),
            Stat::make('Low Stock Alerts', (string) $lowStock)
                ->description('Requires attention')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->url(StockAdjustmentResource::getUrl('create')),
        ];
    }
}
