<?php

namespace App\Filament\Resources\RepackagingFormulas\Tables;

use App\Filament\Support\ImsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepackagingFormulasTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with(['sku', 'rawMaterial.unit', 'unit']))
            ->columns([
                TextColumn::make('sku.sku_code')
                    ->label('SKU Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sku.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Raw Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('consumption_display')
                    ->label('Per Unit')
                    ->alignEnd()
                    ->getStateUsing(fn ($record): string => number_format($record->qty, 4).' '.$record->unit->symbol),
                TextColumn::make('base_qty')
                    ->label('Base / Unit')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 4).' '.$record->rawMaterial->unit->symbol)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('sku_id')
                    ->label('SKU')
                    ->relationship('sku', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('raw_material_id')
                    ->label('Raw material')
                    ->relationship('rawMaterial', 'name')
                    ->searchable()
                    ->preload(),
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
