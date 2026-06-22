<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')->label('Code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
            ]);
    }
}
