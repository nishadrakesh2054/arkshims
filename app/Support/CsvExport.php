<?php

namespace App\Support;

use Closure;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    /**
     * @param  Builder<mixed>  $query
     * @param  array<int, string>  $headers
     * @param  Closure(mixed): array<int, string|int|float|null>  $mapRow
     */
    public static function tableAction(
        string $name,
        string $filename,
        Builder $query,
        array $headers,
        Closure $mapRow,
    ): Action {
        return Action::make($name)
            ->label('Export CSV')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () use ($filename, $query, $headers, $mapRow): StreamedResponse {
                $exportQuery = clone $query;

                return response()->streamDownload(function () use ($exportQuery, $headers, $mapRow): void {
                    $handle = fopen('php://output', 'w');
                    fputcsv($handle, $headers);

                    $exportQuery->chunkById(500, function ($records) use ($handle, $mapRow): void {
                        foreach ($records as $record) {
                            fputcsv($handle, $mapRow($record));
                        }
                    });

                    fclose($handle);
                }, $filename.'-'.now()->format('Y-m-d-His').'.csv');
            });
    }
}
