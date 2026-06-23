<?php

namespace App\Filament\Resources\Dispatches\Schemas;

use App\Filament\Resources\Dispatches\Pages\EditDispatch;
use App\Models\FinishedGoodsBatch;
use App\Models\Sku;
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
                            ->label('Product')
                            ->relationship('sku', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('unit_type')
                            ->label('Dispatch Unit')
                            ->options([
                                'packs' => 'Packs (loose)',
                                'cartons' => 'Whole cartons',
                            ])
                            ->default('packs')
                            ->live()
                            ->dehydrated(false),
                        Select::make('finished_goods_batch_id')
                            ->label('Carton No (optional)')
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
                                        $batch->id => "{$batch->batch_no} ({$batch->available_qty} packs left)",
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        TextInput::make('quantity')
                            ->label(fn (Get $get): string => ($get('unit_type') ?? 'packs') === 'cartons'
                                ? 'Number of Cartons'
                                : 'Number of Packs')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->helperText(function (Get $get): ?string {
                                if (($get('unit_type') ?? 'packs') !== 'cartons' || blank($get('sku_id'))) {
                                    return null;
                                }

                                $ppc = Sku::query()->find($get('sku_id'))?->packs_per_carton ?? 20;

                                return "Each carton = {$ppc} packs (converted automatically).";
                            }),
                    ])
                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                        if (($data['unit_type'] ?? 'packs') === 'cartons' && filled($data['sku_id'])) {
                            $packsPerCarton = Sku::query()->find($data['sku_id'])?->packs_per_carton ?? 20;
                            $data['quantity'] = (int) $data['quantity'] * $packsPerCarton;
                        }

                        unset($data['unit_type']);

                        return $data;
                    })
                    ->columns(4)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]);
    }
}
