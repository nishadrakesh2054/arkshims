<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinishedGoodsStockTableWidget;
use App\Filament\Widgets\InventoryStats;
use App\Filament\Widgets\OperationsStats;
use App\Filament\Widgets\RawMaterialStockTableWidget;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class StockDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Stock Analytics';

    protected static ?string $title = 'Inventory Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            InventoryStats::class,
            OperationsStats::class,
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getFooterWidgets(): array
    {
        return [
            RawMaterialStockTableWidget::class,
            FinishedGoodsStockTableWidget::class,
        ];
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Inventory Analytics';
    }
}
