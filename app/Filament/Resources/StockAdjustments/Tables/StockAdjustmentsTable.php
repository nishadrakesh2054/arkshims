<?php

namespace App\Filament\Resources\StockAdjustments\Tables;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with(['rawMaterial', 'sku', 'user']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('stock_type')
                    ->label('Stock Type')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'raw_material' ? 'warning' : 'info')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'raw_material' => 'Raw Material',
                        'finished_goods' => 'Finished Goods',
                        default => $state,
                    }),
                TextColumn::make('item_name')
                    ->label('Item')
                    ->searchable()
                    ->getStateUsing(fn ($record): string => $record->stock_type === 'raw_material'
                        ? ($record->rawMaterial?->name ?? '—')
                        : ($record->sku?->name ?? '—')),
                TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'increase' ? 'Increase' : 'Decrease')
                    ->color(fn (string $state): string => $state === 'increase' ? 'success' : 'danger'),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('user.name')
                    ->label('Adjusted By')
                    ->placeholder('System')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('stock_type')
                    ->label('Stock type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_goods' => 'Finished Goods',
                    ]),
                SelectFilter::make('direction')
                    ->options([
                        'increase' => 'Increase',
                        'decrease' => 'Decrease',
                    ]),
                ImsFilters::dateRange('adjusted', 'created_at', 'Adjustment date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->label('Reverse'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Reverse selected'),
                ]),
            ]);
    }
}
