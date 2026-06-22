<?php

namespace App\Filament\Resources\MaterialReceipts\Tables;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\RawMaterial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaterialReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with(['rawMaterial', 'supplier', 'unit']))
            ->columns([
                TextColumn::make('received_date')
                    ->label('Received')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('batch_no')
                    ->label('Batch No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Raw Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignEnd()
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->toggleable(),
                TextColumn::make('base_qty')
                    ->label('Base Qty')
                    ->alignEnd()
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ImsFilters::dateRange('received', 'received_date', 'Received date'),
                SelectFilter::make('raw_material_id')
                    ->label('Raw material')
                    ->options(fn (): array => RawMaterial::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('received_date', 'desc')
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
