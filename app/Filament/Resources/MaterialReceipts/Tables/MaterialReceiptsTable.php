<?php

namespace App\Filament\Resources\MaterialReceipts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class MaterialReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rawMaterial.name')->label('Raw Material')->sortable()->searchable(),
                TextColumn::make('supplier.name')->label('Supplier')->sortable()->searchable(),
                TextColumn::make('batch_no')->label('Batch No')->sortable()->searchable(),
                TextColumn::make('qty')->label('Quantity')->sortable()->numeric(decimalPlaces: 2),
                TextColumn::make('unit.name')->label('Unit')->sortable()->searchable(),
                TextColumn::make('base_qty')->label('Base Qty')->sortable()->numeric(decimalPlaces: 2),
                TextColumn::make('received_date')->label('Received Date')->sortable()->searchable(),
                TextColumn::make('remarks')->label('Remarks')->sortable()->searchable(),
            ])
            ->filters([
                //
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
