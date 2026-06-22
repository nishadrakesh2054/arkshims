<?php

namespace App\Filament\Resources\RepackagingBatches;

use App\Filament\Resources\RepackagingBatches\Pages\CreateRepackagingBatch;
use App\Filament\Resources\RepackagingBatches\Pages\EditRepackagingBatch;
use App\Filament\Resources\RepackagingBatches\Pages\ListRepackagingBatches;
use App\Filament\Resources\RepackagingBatches\Schemas\RepackagingBatchForm;
use App\Filament\Resources\RepackagingBatches\Tables\RepackagingBatchesTable;
use App\Models\RepackagingBatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RepackagingBatchResource extends Resource
{
    protected static ?string $model = RepackagingBatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $navigationLabel = 'Repackaging Batches';

    protected static string|\UnitEnum|null $navigationGroup = 'Production';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RepackagingBatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepackagingBatchesTable::configure($table);
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
            'index' => ListRepackagingBatches::route('/'),
            'create' => CreateRepackagingBatch::route('/create'),
            'edit' => EditRepackagingBatch::route('/{record}/edit'),
        ];
    }
}
