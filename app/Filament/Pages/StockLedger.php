<?php

namespace App\Filament\Pages;

use App\Models\InventoryTransaction;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class StockLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Stock Ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.stock-ledger';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryTransaction::query()
                    ->with(['rawMaterial.unit'])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Raw Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('base_qty')
                    ->label('Base Qty')
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->rawMaterial?->unit?->symbol)
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('Reference')
                    ->sortable(),
                TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Stock Ledger';
    }
}
