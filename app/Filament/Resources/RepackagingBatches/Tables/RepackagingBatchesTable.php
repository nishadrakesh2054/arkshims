<?php

namespace App\Filament\Resources\RepackagingBatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepackagingBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('sku'))
            ->columns([
                TextColumn::make('batch_no')
                    ->label('Batch No')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku.name')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty Produced')
                    ->sortable(),
                TextColumn::make('repackaged_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(40),
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
