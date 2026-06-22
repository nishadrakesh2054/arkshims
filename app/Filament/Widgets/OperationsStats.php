<?php

namespace App\Filament\Widgets;

use App\Models\Dispatch;
use App\Models\FinishedGoodsTransaction;
use App\Models\InventoryTransaction;
use App\Models\MaterialReceipt;
use App\Models\RepackagingBatch;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationsStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $rawIn = (float) InventoryTransaction::query()->where('type', 'IN')->sum('base_qty');
        $rawOut = (float) InventoryTransaction::query()->where('type', 'OUT')->sum('base_qty');
        $fgIn = (int) FinishedGoodsTransaction::query()->where('type', 'IN')->sum('qty');
        $fgOut = (int) FinishedGoodsTransaction::query()->where('type', 'OUT')->sum('qty');

        return [
            Stat::make('Receipts Today', MaterialReceipt::query()->whereDate('received_date', today())->count())
                ->description('Material IN entries')
                ->descriptionIcon(Heroicon::OutlinedArrowDownTray)
                ->color('success')
                ->chart([2, 4, 3, 5, 4, 6, MaterialReceipt::query()->whereDate('received_date', today())->count()]),
            Stat::make('Production Batches', RepackagingBatch::query()->count())
                ->description(RepackagingBatch::query()->whereDate('repackaged_date', today())->count().' today')
                ->descriptionIcon(Heroicon::OutlinedCog6Tooth)
                ->color('primary'),
            Stat::make('Dispatches', Dispatch::query()->count())
                ->description(Dispatch::query()->whereDate('dispatched_date', today())->count().' today')
                ->descriptionIcon(Heroicon::OutlinedTruck)
                ->color('info'),
            Stat::make('Net Movement', number_format($rawIn - $rawOut, 1).' kg raw')
                ->description("FG net: {$fgIn} in / {$fgOut} out")
                ->descriptionIcon(Heroicon::OutlinedArrowsRightLeft)
                ->color('warning'),
        ];
    }
}
