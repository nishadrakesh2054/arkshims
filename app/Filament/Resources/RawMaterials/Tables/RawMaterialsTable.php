<?php

namespace App\Filament\Resources\RawMaterials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('unit'))
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('stock_display')
                    ->label('Current Stock')
                    ->getStateUsing(function ($record): string {
                        return number_format(
                            $record->current_stock,
                            2
                        ).' '.$record->unit->symbol;
                    }),
                TextColumn::make('minimum_stock')
                    ->label('Minimum Stock')
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->unit->symbol),
                IconColumn::make('is_low_stock')
                    ->label('Low Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
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
