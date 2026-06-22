<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\InventoryTransaction;
use App\Models\RawMaterial;
use App\Support\CsvExport;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;

class StockLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Raw Stock Ledger';

    protected static ?string $title = 'Raw Material Stock Ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.stock-ledger';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->query(
                InventoryTransaction::query()
                    ->with(['rawMaterial.unit'])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Material')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Movement')
                    ->badge()
                    ->color(fn (string $state): string => ImsTable::transactionTypeColor($state))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                        'ADJUSTMENT' => 'Adjustment',
                        default => $state,
                    }),
                TextColumn::make('base_qty')
                    ->label('Quantity')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->rawMaterial?->unit?->symbol)
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('Source')
                    ->formatStateUsing(fn (?string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->toggleable(),
                TextColumn::make('reference_id')
                    ->label('Ref #')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Movement type')
                    ->options([
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                        'ADJUSTMENT' => 'Adjustment',
                    ]),
                SelectFilter::make('raw_material_id')
                    ->label('Material')
                    ->options(fn (): array => RawMaterial::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                ImsFilters::dateRange('activity', 'created_at', 'Transaction date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CsvExport::tableAction(
                    'exportCsv',
                    'raw-stock-ledger',
                    InventoryTransaction::query()->with(['rawMaterial.unit']),
                    ['Date', 'Raw Material', 'Type', 'Base Qty', 'Unit', 'Reference', 'Ref ID'],
                    fn (InventoryTransaction $record): array => [
                        $record->created_at?->toDateTimeString() ?? '',
                        $record->rawMaterial?->name ?? '',
                        $record->type,
                        (string) $record->base_qty,
                        $record->rawMaterial?->unit?->symbol ?? '',
                        $record->reference_type ?? '',
                        (string) $record->reference_id,
                    ],
                ),
            ]);
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
        return 'Raw Material Stock Ledger';
    }
}
