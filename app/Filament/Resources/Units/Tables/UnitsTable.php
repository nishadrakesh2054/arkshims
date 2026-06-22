<?php

namespace App\Filament\Resources\Units\Tables;

use App\Filament\Support\ImsTable;
use App\Models\Unit;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->columns([
                TextColumn::make('name')
                    ->label('Unit')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('symbol')
                    ->label('Symbol')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('conversion_factor')
                    ->label('Conversion')
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4))
                    ->toggleable(),
                IconColumn::make('is_base')
                    ->label('Base Unit')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Unit type')
                    ->options(fn (): array => Unit::query()
                        ->distinct()
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
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
