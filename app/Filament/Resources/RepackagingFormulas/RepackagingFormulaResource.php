<?php

namespace App\Filament\Resources\RepackagingFormulas;

use App\Filament\Resources\RepackagingFormulas\Pages\CreateRepackagingFormula;
use App\Filament\Resources\RepackagingFormulas\Pages\EditRepackagingFormula;
use App\Filament\Resources\RepackagingFormulas\Pages\ListRepackagingFormulas;
use App\Filament\Resources\RepackagingFormulas\Schemas\RepackagingFormulaForm;
use App\Filament\Resources\RepackagingFormulas\Tables\RepackagingFormulasTable;
use App\Models\RepackagingFormula;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RepackagingFormulaResource extends Resource
{
    protected static ?string $model = RepackagingFormula::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?string $navigationLabel = 'Formulas';

    protected static string|\UnitEnum|null $navigationGroup = 'Production';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Repackaging Formula';

    protected static ?string $pluralModelLabel = 'Repackaging Formulas';

    public static function form(Schema $schema): Schema
    {
        return RepackagingFormulaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepackagingFormulasTable::configure($table);
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
            'index' => ListRepackagingFormulas::route('/'),
            'create' => CreateRepackagingFormula::route('/create'),
            'edit' => EditRepackagingFormula::route('/{record}/edit'),
        ];
    }
}
