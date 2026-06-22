<?php

namespace App\Filament\Support;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class ImsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
            ])
            ->deferFilters()
            ->persistFiltersInSession()
            ->paginationPageOptions([10, 25, 50, 100])
            ->emptyStateHeading('No records found')
            ->emptyStateDescription('Try adjusting your search or filters.');
    }

    public static function transactionTypeColor(string $type): string
    {
        return match ($type) {
            'IN' => 'success',
            'OUT' => 'danger',
            'ADJUSTMENT' => 'warning',
            default => 'gray',
        };
    }
}
