<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Name')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->required()->email(),
                TextInput::make('phone')->label('Phone')->required()->maxLength(255),
                TextInput::make('address')->label('Address')->required()->maxLength(255),
                TextInput::make('city')->label('City')->required()->maxLength(255),
                TextInput::make('country')->label('Country')->required()->maxLength(255),
            ]);
    }
}
