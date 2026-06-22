<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\ProductCategory;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Name')
                    ->required()
                    ->maxLength(255),
                Select::make('product_category_id')->label('Product Category')
                    ->required()
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload()
                   
            ]);
    }
}
