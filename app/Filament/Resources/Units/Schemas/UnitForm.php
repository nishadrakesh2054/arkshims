<?php

namespace App\Filament\Resources\Units\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Name')->required()->maxLength(255),
                TextInput::make('symbol')->label('Symbol')->required()->maxLength(255),
                Select::make('type')->label('Type')->required()->options([
                    'weight' => 'Weight',
                    'volume' => 'Volume',
                    'count' => 'Count',
                ])->searchable()->preload(),
                TextInput::make('conversion_factor')->label('Conversion Factor')->required()->numeric()->step(0.0001),
                Toggle::make('is_base')->label('Is Base')->required(),
            ]);
    }
}
