<?php

namespace App\Filament\Resources\RepackagingBatches\Schemas;

use App\Filament\Resources\RepackagingBatches\Pages\EditRepackagingBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Livewire\Component;

class RepackagingBatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('sku_id')
                    ->label('SKU')
                    ->relationship('sku', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Component $livewire): bool => $livewire instanceof EditRepackagingBatch),
                TextInput::make('batch_no')
                    ->label('Batch No')
                    ->required()
                    ->maxLength(255),
                TextInput::make('quantity')
                    ->label('Quantity Produced')
                    ->helperText('Number of SKU units to repackage. Raw material is deducted automatically.')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->disabled(fn (Component $livewire): bool => $livewire instanceof EditRepackagingBatch),
                DatePicker::make('repackaged_date')
                    ->label('Repackaged Date')
                    ->required()
                    ->default(now()),
                TextInput::make('remarks')
                    ->label('Remarks')
                    ->maxLength(255),
            ]);
    }
}
