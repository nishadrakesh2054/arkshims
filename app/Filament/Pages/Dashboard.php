<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InventoryStats;
use App\Filament\Widgets\LowStockTableWidget;
use App\Filament\Widgets\OperationsStats;
use App\Filament\Widgets\RecentActivityWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Operations Dashboard';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            InventoryStats::class,
            OperationsStats::class,
            LowStockTableWidget::class,
            RecentActivityWidget::class,
        ];
    }

    /**
     * @return int|array<string, int|null>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
