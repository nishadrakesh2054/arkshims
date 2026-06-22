<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\FinishedGoodsTransaction;
use App\Models\Sku;
use App\Support\CsvExport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class FinishedGoodsMovementReportWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Finished Goods Movement')
            ->description('SKU-level IN from production, OUT from dispatch, and adjustments')
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
                    ->sortable()
                    ->limit(35),
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
                    ->suffix(' pcs'),
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
                    'exportFgMovement',
                    'fg-movement-report',
                    FinishedGoodsTransaction::query()->with(['sku', 'finishedGoodsBatch']),
                    ['Date', 'SKU Code', 'Product', 'Type', 'Qty', 'FG Batch', 'Source'],
                    fn (FinishedGoodsTransaction $record): array => [
                        $record->created_at?->toDateTimeString() ?? '',
                        $record->sku?->sku_code ?? '',
                        $record->sku?->name ?? '',
                        $record->type,
                        (string) $record->qty,
                        $record->finishedGoodsBatch?->batch_no ?? '',
                        $record->reference_type ?? '',
                    ],
                ),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
