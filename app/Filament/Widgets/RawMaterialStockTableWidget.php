<?php

namespace App\Filament\Widgets;

use App\Filament\Support\ImsTable;
use App\Models\RawMaterial;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RawMaterialStockTableWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Raw Material Stock Position')
            ->description('Live on-hand quantities with minimum levels')
            ->query(
                RawMaterial::query()
                    ->with('unit')
                    ->withCurrentStock()
                    ->orderBy('name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->icon(Heroicon::OutlinedCube)
                    ->weight('medium'),
                TextColumn::make('current_stock')
                    ->label('On Hand')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, RawMaterial $record): string => number_format((float) $state, 2).' '.$record->unit->symbol)
                    ->color(fn (RawMaterial $record): string => $record->is_low_stock ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('minimum_stock')
                    ->label('Minimum')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, RawMaterial $record): string => number_format((float) $state, 2).' '.$record->unit->symbol)
                    ->toggleable(),
                TextColumn::make('cost_per_unit')
                    ->label('Unit Cost')
                    ->money('INR')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock_value')
                    ->label('Stock Value')
                    ->alignEnd()
                    ->money('INR')
                    ->getStateUsing(fn (RawMaterial $record): float => $record->stock_value)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                                WHEN type = \'IN\' THEN base_qty
                                WHEN type = \'OUT\' THEN -base_qty
                                WHEN type = \'ADJUSTMENT\' THEN base_qty
                                ELSE 0
                            END), 0) FROM inventory_transactions WHERE inventory_transactions.raw_material_id = raw_materials.id) < minimum_stock'
                        )),
            ])
            ->paginated([10, 25, 50]);
    }
}
