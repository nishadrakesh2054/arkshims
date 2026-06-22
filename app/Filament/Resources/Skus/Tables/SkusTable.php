<?php

namespace App\Filament\Resources\Skus\Tables;

use App\Filament\Support\ImsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SkusTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with(['productCategory', 'brand', 'unit'])->withCurrentStock())
            ->columns([
                TextColumn::make('sku_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon(Heroicon::OutlinedTag),
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('packaging_type')
                    ->label('Package')
                    ->badge()
                    ->color('info'),
                TextColumn::make('weight_display')
                    ->label('Weight')
                    ->getStateUsing(fn ($record): string => number_format($record->weight, 2).' '.$record->unit->symbol)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('current_stock')
                    ->label('FG Stock')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state, $record): string => $record->is_low_stock ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->suffix(' pcs'),
                TextColumn::make('minimum_stock')
                    ->label('Min')
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
                SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active status')
                    ->placeholder('All SKUs')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
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
