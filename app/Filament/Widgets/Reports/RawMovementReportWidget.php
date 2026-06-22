<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\InventoryTransaction;
use App\Models\RawMaterial;
use App\Support\CsvExport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RawMovementReportWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Raw Material Movement')
            ->description('Complete ledger of bulk stock IN, OUT, and adjustments')
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
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->rawMaterial?->unit?->symbol),
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
                    'exportRawMovement',
                    'raw-movement-report',
                    InventoryTransaction::query()->with(['rawMaterial.unit']),
                    ['Date', 'Material', 'Type', 'Qty', 'Unit', 'Source', 'Ref'],
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
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
