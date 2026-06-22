<?php

namespace App\Filament\Widgets;

use App\Filament\Support\ImsTable;
use App\Models\Sku;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class FinishedGoodsStockTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Finished Goods Stock Position')
            ->description('SKU on-hand units ready for dispatch')
            ->query(
                Sku::query()
                    ->with(['unit', 'brand'])
                    ->withCurrentStock()
                    ->where('is_active', true)
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('sku_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedTag),
                TextColumn::make('name')
                    ->label('SKU')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                TextColumn::make('packaging_type')
                    ->label('Package')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_stock')
                    ->label('On Hand')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state, Sku $record): string => $record->is_low_stock ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->suffix(' pcs'),
                TextColumn::make('minimum_stock')
                    ->label('Minimum')
                    ->alignEnd()
                    ->suffix(' pcs')
                    ->toggleable(),
                IconColumn::make('is_low_stock')
                    ->label('Alert')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)
                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                    ->trueColor('danger')
                    ->falseColor('success'),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Low stock only')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->where('minimum_stock', '>', 0)
                        ->whereRaw(
                            '(SELECT COALESCE(SUM(CASE
                                WHEN type = \'IN\' THEN qty
                                WHEN type = \'OUT\' THEN -qty
                                WHEN type = \'ADJUSTMENT\' THEN qty
                                ELSE 0
                            END), 0) FROM finished_goods_transactions WHERE finished_goods_transactions.sku_id = skus.id) < minimum_stock'
                        )),
            ])
            ->paginated([10, 25, 50]);
    }
}
