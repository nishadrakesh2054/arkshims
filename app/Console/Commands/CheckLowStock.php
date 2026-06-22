<?php

namespace App\Console\Commands;

use App\Models\RawMaterial;
use App\Models\Sku;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    protected $signature = 'ims:check-low-stock';

    protected $description = 'Log low-stock raw materials and finished goods SKUs';

    public function handle(): int
    {
        $rawLow = RawMaterial::query()
            ->with('unit')
            ->where('minimum_stock', '>', 0)
            ->withCurrentStock()
            ->get()
            ->filter(fn (RawMaterial $material): bool => $material->is_low_stock);

        $fgLow = Sku::query()
            ->where('is_active', true)
            ->where('minimum_stock', '>', 0)
            ->withCurrentStock()
            ->get()
            ->filter(fn (Sku $sku): bool => $sku->is_low_stock);

        if ($rawLow->isEmpty() && $fgLow->isEmpty()) {
            $this->components->info('No low-stock items.');

            return self::SUCCESS;
        }

        if ($rawLow->isNotEmpty()) {
            $this->components->warn('Low raw materials: '.$rawLow->count());
            $this->table(
                ['Material', 'Current', 'Minimum'],
                $rawLow->map(fn (RawMaterial $m): array => [
                    $m->name,
                    number_format($m->current_stock, 2).' '.$m->unit->symbol,
                    number_format($m->minimum_stock, 2).' '.$m->unit->symbol,
                ])->all(),
            );
        }

        if ($fgLow->isNotEmpty()) {
            $this->components->warn('Low finished goods: '.$fgLow->count());
            $this->table(
                ['SKU', 'Current', 'Minimum'],
                $fgLow->map(fn (Sku $s): array => [
                    $s->name,
                    (string) $s->current_stock,
                    (string) $s->minimum_stock,
                ])->all(),
            );
        }

        Log::warning('IMS low stock alert', [
            'raw_materials' => $rawLow->pluck('name')->all(),
            'finished_goods' => $fgLow->pluck('name')->all(),
        ]);

        return self::SUCCESS;
    }
}
