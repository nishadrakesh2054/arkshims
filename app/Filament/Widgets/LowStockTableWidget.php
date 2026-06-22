<?php

namespace App\Filament\Widgets;

use App\Filament\Support\ImsTable;
use App\Models\RawMaterial;
use App\Models\Sku;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Collection;

class LowStockTableWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Low Stock Alerts')
            ->description('Items below minimum threshold — create a receipt or production batch to replenish')
            ->records(fn (): Collection => $this->getLowStockRecords())
            ->columns([
                TextColumn::make('category')
                    ->label('Type')
                    ->badge()
                    ->color(fn (array $record): string => $record['category'] === 'Raw Material' ? 'warning' : 'info'),
                TextColumn::make('name')
                    ->label('Item')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('on_hand')
                    ->label('On Hand')
                    ->alignEnd(),
                TextColumn::make('minimum')
                    ->label('Minimum')
                    ->alignEnd(),
                TextColumn::make('shortage')
                    ->label('Short By')
                    ->alignEnd()
                    ->color('danger')
                    ->weight('semibold'),
            ])
            ->paginated([5, 10, 25])
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('All stock levels healthy')
            ->emptyStateDescription('No raw materials or SKUs are below their minimum thresholds.');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getLowStockRecords(): Collection
    {
        $raw = RawMaterial::query()
            ->withCurrentStock()
            ->with('unit')
            ->where('minimum_stock', '>', 0)
            ->orderBy('name')
            ->get()
            ->filter(fn (RawMaterial $material): bool => $material->is_low_stock)
            ->map(fn (RawMaterial $material): array => [
                'id' => 'raw-'.$material->id,
                'category' => 'Raw Material',
                'name' => $material->name,
                'on_hand' => number_format($material->current_stock, 2).' '.$material->unit->symbol,
                'minimum' => number_format($material->minimum_stock, 2).' '.$material->unit->symbol,
                'shortage' => number_format($material->minimum_stock - $material->current_stock, 2).' '.$material->unit->symbol,
            ]);

        $fg = Sku::query()
            ->withCurrentStock()
            ->where('is_active', true)
            ->where('minimum_stock', '>', 0)
            ->orderBy('name')
            ->get()
            ->filter(fn (Sku $sku): bool => $sku->is_low_stock)
            ->map(fn (Sku $sku): array => [
                'id' => 'sku-'.$sku->id,
                'category' => 'Finished Goods',
                'name' => $sku->name,
                'on_hand' => $sku->current_stock.' pcs',
                'minimum' => $sku->minimum_stock.' pcs',
                'shortage' => ($sku->minimum_stock - $sku->current_stock).' pcs',
            ]);

        return $raw->merge($fg)->values();
    }
}
