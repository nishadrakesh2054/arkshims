<?php

namespace App\Filament\Resources\MaterialReceipts\Schemas;

use App\Filament\Resources\MaterialReceipts\Pages\EditMaterialReceipt;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Livewire\Component;

class MaterialReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('raw_material_id')
                    ->label('Raw Material')
                    ->required()
                    ->relationship('rawMaterial', 'name')
                    ->disabled(fn (Component $livewire): bool => $livewire instanceof EditMaterialReceipt),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->required()
                    ->relationship('supplier', 'name'),
                TextInput::make('batch_no')
                    ->label('Batch No')
                    ->required()
                    ->maxLength(255),
                TextInput::make('qty')
                    ->label('Quantity')
                    ->required()
                    ->numeric()
                    ->step(0.0001)
                    ->live()
                    ->helperText(fn (Component $livewire): ?string => $livewire instanceof EditMaterialReceipt
                        ? 'Updating quantity recalculates the linked stock IN transaction.'
                        : null),
                Select::make('unit_id')
                    ->label('Unit')
                    ->required()
                    ->relationship('unit', 'name'),
                Hidden::make('base_qty'),
                DatePicker::make('received_date')
                    ->label('Received Date')
                    ->required(),
                TextInput::make('remarks')
                    ->label('Remarks')
                    ->nullable()
                    ->maxLength(255),
            ]);
    }
}
