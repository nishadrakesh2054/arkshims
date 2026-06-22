<?php

namespace App\Filament\Resources\Dispatches\Schemas;

use App\Filament\Resources\Dispatches\Pages\EditDispatch;
use App\Models\FinishedGoodsBatch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Livewire\Component;

class DispatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dispatch_no')
                    ->label('Dispatch No')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('customer_name')
                    ->label('Customer')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('dispatched_date')
                    ->label('Dispatch Date')
                    ->required()
                    ->default(now()),
                TextInput::make('remarks')
                    ->label('Remarks')
                    ->maxLength(255),
                Repeater::make('items')
                    ->relationship()
                    ->label('Dispatch Items')
                    ->disabled(fn (Component $livewire): bool => $livewire instanceof EditDispatch)
                    ->schema([
                        Select::make('sku_id')
                            ->label('SKU')
                            ->relationship('sku', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('finished_goods_batch_id')
                            ->label('Production Batch (optional)')
                            ->options(function (Get $get): array {
                                if (blank($get('sku_id'))) {
                                    return [];
                                }

                                return FinishedGoodsBatch::query()
                                    ->where('sku_id', $get('sku_id'))
                                    ->orderByDesc('produced_date')
                                    ->get()
                                    ->filter(fn (FinishedGoodsBatch $batch): bool => $batch->available_qty > 0)
                                    ->mapWithKeys(fn (FinishedGoodsBatch $batch): array => [
                                        $batch->id => "{$batch->batch_no} (available: {$batch->available_qty})",
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                    ])
                    ->columns(3)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }
}
