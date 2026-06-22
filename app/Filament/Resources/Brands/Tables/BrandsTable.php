<?php

namespace App\Filament\Resources\Brands\Tables;

use App\Filament\Support\ImsTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return ImsTable::configure($table)
            ->columns([
                TextColumn::make('name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('productCategory.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                SelectFilter::make('product_category_id')
                    ->label('Category')
                    ->relationship('productCategory', 'name')
                    ->searchable()
                    ->preload(),
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
