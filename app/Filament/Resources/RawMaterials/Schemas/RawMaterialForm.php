<?php

namespace App\Filament\Resources\RawMaterials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RawMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                Select::make('unit_id')
                    ->label('Unit')
                    ->required()
                    ->relationship('unit', 'name'),
                TextInput::make('minimum_stock')
                    ->label('Minimum Stock')
                    ->numeric()
                    ->step(0.0001)
                    ->default(0)
                    ->required(),
                TextInput::make('cost_per_unit')
                    ->label('Cost Per Base Unit')
                    ->numeric()
                    ->step(0.0001)
                    ->default(0)
                    ->prefix('₹')
                    ->required(),
            ]);
    }
}
