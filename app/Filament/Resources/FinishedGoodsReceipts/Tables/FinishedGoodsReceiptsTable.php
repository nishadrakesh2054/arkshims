<?php

namespace App\Filament\Resources\FinishedGoodsReceipts\Tables;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinishedGoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with(['sku', 'supplier']))
            ->columns([
                TextColumn::make('received_date')
                    ->label('Received')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('receipt_no')
                    ->label('Receipt No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('sku.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cartons_count')
                    ->label('Cartons')
                    ->alignEnd()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('packs_per_carton')
                    ->label('Packs/Carton')
                    ->alignEnd(),
                TextColumn::make('total_packs')
                    ->label('Total Packs IN')
                    ->alignEnd()
                    ->getStateUsing(fn ($record): int => $record->total_packs)
                    ->badge()
                    ->color('success'),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->toggleable(),
                TextColumn::make('remarks')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sku_id')
                    ->label('Product')
                    ->relationship('sku', 'name')
                    ->searchable()
                    ->preload(),
                ImsFilters::dateRange('received', 'received_date', 'Received date'),
            ])
            ->defaultSort('received_date', 'desc')
            ->recordActions([
                DeleteAction::make()
                    ->label('Reverse'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->label('Reverse selected'),
            ]);
    }
}
