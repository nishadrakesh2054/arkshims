<?php

namespace App\Filament\Pages;

use App\Filament\Support\ImsFilters;
use App\Filament\Support\ImsTable;
use App\Models\FinishedGoodsBatch;
use App\Models\Sku;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class BatchTraceability extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static ?string $navigationLabel = 'Batch Traceability';

    protected static ?string $title = 'Batch Traceability';

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.batch-traceability';

    public function table(Table $table): Table
    {
        return ImsTable::configure($table)
            ->query(
                FinishedGoodsBatch::query()
                    ->with(['sku', 'repackagingBatch'])
            )
            ->columns([
                TextColumn::make('batch_no')
                    ->label('FG Batch')
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
                    ->limit(35),
                TextColumn::make('quantity')
                    ->label('Produced')
                    ->alignEnd()
                    ->suffix(' pcs')
                    ->sortable(),
                TextColumn::make('available_qty')
                    ->label('Available')
                    ->alignEnd()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray')
                    ->sortable(),
                TextColumn::make('dispatched_qty')
                    ->label('Dispatched')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('produced_date')
                    ->label('Produced On')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('repackagingBatch.batch_no')
                    ->label('Production Batch')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('sku_id')
                    ->label('SKU')
                    ->options(fn (): array => Sku::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                ImsFilters::dateRange('produced', 'produced_date', 'Production date'),
                Filter::make('has_stock')
                    ->label('Has available stock')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('quantity', '>', 0)),
            ])
            ->defaultSort('produced_date', 'desc');
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
        return 'Batch Traceability';
    }
}
