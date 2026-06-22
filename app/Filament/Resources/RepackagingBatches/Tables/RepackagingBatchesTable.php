<?php

namespace App\Filament\Resources\RepackagingBatches\Tables;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\RepackagingBatch;
use App\Support\CsvExport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepackagingBatchesTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->with('sku'))
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
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Qty Produced')
                    ->alignEnd()
                    ->suffix(' pcs')
                    ->sortable(),
                TextColumn::make('repackaged_date')
                    ->label('Production Date')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Notes')
                    ->limit(35)
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
                    'exportBatches',
                    'production-batches',
                    RepackagingBatch::query()->with('sku'),
                    ['Batch', 'SKU', 'Qty', 'Date', 'Remarks'],
                    fn (RepackagingBatch $record): array => [
                        $record->batch_no,
                        $record->sku?->name ?? '',
                        (string) $record->quantity,
                        $record->repackaged_date?->toDateString() ?? '',
                        $record->remarks ?? '',
                    ],
                ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
