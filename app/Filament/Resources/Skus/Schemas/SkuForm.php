<?php

namespace App\Filament\Resources\Skus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SkuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_category_id')
                    ->label('Product Category')
                    ->relationship('productCategory', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('brand_id', null)),
                Select::make('brand_id')
                    ->label('Brand')
                    ->relationship(
                        'brand',
                        'name',
                        fn (Builder $query, Get $get): Builder => $query->when(
                            $get('product_category_id'),
                            fn (Builder $query, int $categoryId): Builder => $query->where('product_category_id', $categoryId),
                        ),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get): bool => blank($get('product_category_id'))),
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sku_code')
                    ->label('SKU Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('weight')
                    ->label('Weight')
                    ->required()
                    ->numeric()
                    ->step(0.0001),
                Select::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('packaging_type')
                    ->label('Packaging Type')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Is Active')
                    ->default(true),
                TextInput::make('packs_per_carton')
                    ->label('Packs per Carton')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->helperText('For chocolate / carton products (e.g. 20). Leave empty for coffee pouch-only SKUs.')
                    ->nullable(),
                TextInput::make('minimum_stock')
                    ->label('Minimum FG Stock')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ]);
    }
}
