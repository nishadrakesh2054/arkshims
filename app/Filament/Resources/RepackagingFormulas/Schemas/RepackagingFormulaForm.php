<?php

namespace App\Filament\Resources\RepackagingFormulas\Schemas;

use App\Models\RawMaterial;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class RepackagingFormulaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sku_id')
                    ->label('SKU (Output Product)')
                    ->relationship('sku', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Select::make('raw_material_id')
                    ->label('Raw Material')
                    ->relationship('rawMaterial', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                        if (blank($state)) {
                            return;
                        }

                        $unitId = RawMaterial::query()->find($state)?->unit_id;

                        if ($unitId) {
                            $set('unit_id', $unitId);
                        }
                    }),
                TextInput::make('qty')
                    ->label('Consumption Per Unit')
                    ->helperText('Raw material needed to produce 1 SKU unit.')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->live(),
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Hidden::make('base_qty'),
            ]);
    }
}
