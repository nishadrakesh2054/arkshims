<?php

namespace App\Filament\Resources\FinishedGoodsReceipts\Schemas;

use App\Models\Sku;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FinishedGoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('receipt_no')
                    ->label('Receipt No')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('e.g. FG-VAN-2026-001'),
                Select::make('sku_id')
                    ->label('Product (Pack SKU)')
                    ->relationship(
                        'sku',
                        'name',
                        fn ($query) => $query->where('is_active', true)->orderBy('name'),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set, ?int $state): void {
                        if (blank($state)) {
                            return;
                        }

                        $sku = Sku::query()->find($state);
                        if ($sku?->packs_per_carton) {
                            $set('packs_per_carton', $sku->packs_per_carton);
                        }
                    }),
                Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('cartons_count')
                    ->label('Number of Cartons')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->live(),
                TextInput::make('packs_per_carton')
                    ->label('Packs per Carton')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(20)
                    ->live(),
                TextInput::make('carton_prefix')
                    ->label('Carton Number Prefix')
                    ->helperText('Optional. Cartons will be named PREFIX-C001, C002, …')
                    ->maxLength(50),
                Placeholder::make('total_packs')
                    ->label('Total Packs (IN)')
                    ->content(function (Get $get): string {
                        $cartons = (int) $get('cartons_count');
                        $packs = (int) $get('packs_per_carton');

                        if ($cartons <= 0 || $packs <= 0) {
                            return '—';
                        }

                        return number_format($cartons * $packs).' packs';
                    }),
                DatePicker::make('received_date')
                    ->label('Received Date')
                    ->required()
                    ->default(now()),
                TextInput::make('remarks')
                    ->label('Remarks')
                    ->maxLength(255),
            ]);
    }
}
