<?php

namespace App\Filament\Resources\RawMaterials\Tables;

use App\Filament\Support\ImsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RawMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with('unit')->withCurrentStock())
            ->columns([
                TextColumn::make('name')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->icon(Heroicon::OutlinedCube)
                    ->weight('medium'),
                TextColumn::make('stock_display')
                    ->label('On Hand')
                    ->alignEnd()
                    ->getStateUsing(fn ($record): string => number_format($record->current_stock, 2).' '.$record->unit->symbol)
                    ->color(fn ($record): string => $record->is_low_stock ? 'danger' : 'success'),
                TextColumn::make('minimum_stock')
                    ->label('Minimum')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->unit->symbol)
                    ->toggleable(),
                TextColumn::make('cost_per_unit')
                    ->label('Unit Cost')
                    ->money('INR')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('stock_value')
                    ->label('Value')
                    ->money('INR')
                    ->alignEnd()
                    ->getStateUsing(fn ($record): float => $record->stock_value)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_low_stock')
                    ->label('Status')
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
