<?php

namespace App\Filament\Resources\MaterialReceipts;

use App\Filament\Resources\MaterialReceipts\Pages\CreateMaterialReceipt;
use App\Filament\Resources\MaterialReceipts\Pages\EditMaterialReceipt;
use App\Filament\Resources\MaterialReceipts\Pages\ListMaterialReceipts;
use App\Filament\Resources\MaterialReceipts\Schemas\MaterialReceiptForm;
use App\Filament\Resources\MaterialReceipts\Tables\MaterialReceiptsTable;
use App\Models\MaterialReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaterialReceiptResource extends Resource
{
    protected static ?string $model = MaterialReceipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Receipts';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MaterialReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaterialReceipts::route('/'),
            'create' => CreateMaterialReceipt::route('/create'),
            'edit' => EditMaterialReceipt::route('/{record}/edit'),
        ];
    }
}
