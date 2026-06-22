<?php

namespace App\Filament\Widgets\Reports;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\Dispatch;
use App\Support\CsvExport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DispatchReportWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->heading('Dispatch & Sales Report')
            ->description('Outbound shipments to customers')
            ->query(
                Dispatch::query()
                    ->withCount('items')
            )
            ->columns([
                TextColumn::make('dispatch_no')
                    ->label('Dispatch No')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Line Items')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('dispatched_date')
                    ->label('Dispatch Date')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('remarks')
                    ->label('Notes')
                    ->limit(35)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ImsFilters::dateRange('dispatch_date', 'dispatched_date', 'Dispatch date'),
            ])
            ->defaultSort('dispatched_date', 'desc')
            ->headerActions([
                CsvExport::tableAction(
                    'exportDispatches',
                    'dispatch-report',
                    Dispatch::query()->withCount('items'),
                    ['Dispatch No', 'Customer', 'Line Items', 'Date', 'Remarks'],
                    fn (Dispatch $record): array => [
                        $record->dispatch_no,
                        $record->customer_name,
                        (string) $record->items_count,
                        $record->dispatched_date?->toDateString() ?? '',
                        $record->remarks ?? '',
                    ],
                ),
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
