<?php

namespace App\Filament\Resources\FinishedGoodsReceipts;

use App\Filament\Resources\FinishedGoodsReceipts\Pages\CreateFinishedGoodsReceipt;
use App\Filament\Resources\FinishedGoodsReceipts\Pages\ListFinishedGoodsReceipts;
use App\Filament\Resources\FinishedGoodsReceipts\Schemas\FinishedGoodsReceiptForm;
use App\Filament\Resources\FinishedGoodsReceipts\Tables\FinishedGoodsReceiptsTable;
use App\Models\FinishedGoodsReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinishedGoodsReceiptResource extends Resource
{
    protected static ?string $model = FinishedGoodsReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected static ?string $navigationLabel = 'FG Receipts (Cartons)';

    protected static ?string $modelLabel = 'FG Carton Receipt';

    protected static ?string $pluralModelLabel = 'FG Carton Receipts';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return FinishedGoodsReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinishedGoodsReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinishedGoodsReceipts::route('/'),
            'create' => CreateFinishedGoodsReceipt::route('/create'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
