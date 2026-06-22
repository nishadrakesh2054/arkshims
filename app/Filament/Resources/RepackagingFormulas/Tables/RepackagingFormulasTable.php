<?php

namespace App\Filament\Resources\RepackagingFormulas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepackagingFormulasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['sku', 'rawMaterial.unit', 'unit']))
            ->columns([
                TextColumn::make('sku.name')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku.sku_code')
                    ->label('SKU Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Raw Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumption_display')
                    ->label('Per Unit')
                    ->getStateUsing(fn ($record): string => number_format($record->qty, 4).' '.$record->unit->symbol),
                TextColumn::make('base_qty')
                    ->label('Base Qty / Unit')
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 4).' '.$record->rawMaterial->unit->symbol),
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
