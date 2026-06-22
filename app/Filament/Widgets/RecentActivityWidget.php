<?php

namespace App\Filament\Widgets;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\InventoryTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivityWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Recent Stock Activity')
            ->description('Latest raw material ledger movements')
            ->query(
                InventoryTransaction::query()
                    ->with(['rawMaterial.unit'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, H:i')
                    ->sortable(),
                TextColumn::make('rawMaterial.name')
                    ->label('Material')
                    ->searchable()
                    ->limit(30),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => ImsTable::transactionTypeColor($state))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                        'ADJUSTMENT' => 'Adjustment',
                        default => $state,
                    }),
                TextColumn::make('base_qty')
                    ->label('Qty')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2).' '.$record->rawMaterial?->unit?->symbol),
                TextColumn::make('reference_type')
                    ->label('Source')
                    ->formatStateUsing(fn (?string $state): string => str($state)->replace('_', ' ')->title()->toString())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'IN' => 'Stock In',
                        'OUT' => 'Stock Out',
                        'ADJUSTMENT' => 'Adjustment',
                    ]),
                ImsFilters::dateRange('created', 'created_at', 'Activity date'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10]);
    }
}
