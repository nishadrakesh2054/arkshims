<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\RepackagingBatch;
use App\Support\CsvExport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ProductionReportWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Production Report')
            ->description('Repackaging batches and finished goods output')
            ->query(
                RepackagingBatch::query()
                    ->with(['sku.brand'])
            )
            ->columns([
                TextColumn::make('batch_no')
                    ->label('Batch No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('sku.sku_code')
                    ->label('SKU Code')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sku.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('sku.packaging_type')
                    ->label('Package')
                    ->badge()
                    ->color('info'),
                TextColumn::make('quantity')
                    ->label('Qty Produced')
                    ->alignEnd()
                    ->sortable()
                    ->suffix(' pcs'),
                TextColumn::make('repackaged_date')
                    ->label('Production Date')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Notes')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ImsFilters::dateRange('production_date', 'repackaged_date', 'Production date'),
                SelectFilter::make('sku_id')
                    ->label('SKU')
                    ->relationship('sku', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('repackaged_date', 'desc')
            ->headerActions([
                CsvExport::tableAction(
                    'exportProduction',
                    'production-report',
                    RepackagingBatch::query()->with('sku'),
                    ['Batch', 'SKU Code', 'Product', 'Package', 'Qty', 'Date', 'Remarks'],
                    fn (RepackagingBatch $record): array => [
                        $record->batch_no,
                        $record->sku?->sku_code ?? '',
                        $record->sku?->name ?? '',
                        $record->sku?->packaging_type ?? '',
                        (string) $record->quantity,
                        $record->repackaged_date?->toDateString() ?? '',
                        $record->remarks ?? '',
                    ],
                ),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
