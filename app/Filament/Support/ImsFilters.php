<?php

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class ImsFilters
{
    public static function dateRange(string $name, string $column, ?string $label = null): Filter
    {
        return Filter::make($name)
            ->label($label ?? 'Date range')
            ->schema([
                DatePicker::make('from')->label('From'),
                DatePicker::make('until')->label('Until'),
            ])
            ->columns(2)
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        $data['from'] ?? null,
                        fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date),
                    )
                    ->when(
                        $data['until'] ?? null,
                        fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators['from'] = 'From '.$data['from'];
                }

                if ($data['until'] ?? null) {
                    $indicators['until'] = 'Until '.$data['until'];
                }

                return $indicators;
            });
    }

    /**
     * @param  array<string, string>  $options
     */
    public static function select(string $name, string $column, array $options, ?string $label = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->label($label ?? ucfirst(str_replace('_', ' ', $name)))
            ->options($options)
            ->attribute($column);
    }
}
