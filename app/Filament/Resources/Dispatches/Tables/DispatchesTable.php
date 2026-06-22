<?php

namespace App\Filament\Resources\Dispatches\Tables;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\Dispatch;
use App\Support\CsvExport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DispatchesTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->modifyQueryUsing(fn ($query) => $query->withCount('items'))
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
                    ->label('Items')
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
                    'dispatches',
                    Dispatch::query()->withCount('items'),
                    ['Dispatch No', 'Customer', 'Items', 'Date', 'Remarks'],
                    fn (Dispatch $record): array => [
                        $record->dispatch_no,
                        $record->customer_name,
                        (string) $record->items_count,
                        $record->dispatched_date?->toDateString() ?? '',
                        $record->remarks ?? '',
                    ],
                ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
