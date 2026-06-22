<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\FinishedGoodsTransaction;
use App\Models\Sku;
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

class FinishedGoodsLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'FG Stock Ledger';

    protected static ?string $title = 'Finished Goods Stock Ledger';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.finished-goods-ledger';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->query(
                FinishedGoodsTransaction::query()
                    ->with(['sku', 'finishedGoodsBatch'])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                TextColumn::make('sku.sku_code')
                    ->label('SKU Code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sku.name')
                    ->label('Product')
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
                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignEnd()
                    ->suffix(' pcs')
                    ->sortable(),
                TextColumn::make('finishedGoodsBatch.batch_no')
                    ->label('FG Batch')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reference_type')
                    ->label('Source')
                    ->formatStateUsing(fn (?string $state): string => str($state)->replace('_', ' ')->title()->toString())
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
                SelectFilter::make('sku_id')
                    ->label('SKU')
                    ->options(fn (): array => Sku::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                ImsFilters::dateRange('activity', 'created_at', 'Transaction date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CsvExport::tableAction(
                    'exportCsv',
                    'fg-stock-ledger',
                    FinishedGoodsTransaction::query()->with(['sku', 'finishedGoodsBatch']),
                    ['Date', 'SKU', 'Type', 'Qty', 'FG Batch', 'Reference', 'Ref ID'],
                    fn (FinishedGoodsTransaction $record): array => [
                        $record->created_at?->toDateTimeString() ?? '',
                        $record->sku?->name ?? '',
                        $record->type,
                        (string) $record->qty,
                        $record->finishedGoodsBatch?->batch_no ?? '',
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
        return 'Finished Goods Stock Ledger';
    }
}
