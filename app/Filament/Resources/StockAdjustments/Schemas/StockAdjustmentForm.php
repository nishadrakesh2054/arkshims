<?php

namespace App\Filament\Resources\StockAdjustments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('stock_type')
                    ->label('Stock Type')
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_goods' => 'Finished Goods',
                    ])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('raw_material_id', null);
                        $set('sku_id', null);
                    }),
                Select::make('raw_material_id')
                    ->label('Raw Material')
                    ->relationship('rawMaterial', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('stock_type') === 'raw_material')
                    ->visible(fn (Get $get): bool => $get('stock_type') === 'raw_material'),
                Select::make('sku_id')
                    ->label('SKU')
                    ->relationship('sku', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => $get('stock_type') === 'finished_goods')
                    ->visible(fn (Get $get): bool => $get('stock_type') === 'finished_goods'),
                Select::make('direction')
                    ->label('Direction')
                    ->options([
                        'increase' => 'Increase (+)',
                        'decrease' => 'Decrease (−)',
                    ])
                    ->required(),
                TextInput::make('qty')
                    ->label('Quantity')
                    ->required()
                    ->numeric()
                    ->minValue(0.0001)
                    ->step(fn (Get $get): string => $get('stock_type') === 'finished_goods' ? '1' : '0.0001'),
                TextInput::make('reason')
                    ->label('Reason')
                    ->required()
                    ->maxLength(255),
                Textarea::make('remarks')
                    ->label('Remarks')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }
}
