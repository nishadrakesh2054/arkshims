<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\MaterialReceipt;
use App\Models\RawMaterial;
use App\Models\RepackagingBatch;
use App\Models\RepackagingFormula;
use App\Models\Sku;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class VerifyImsSetup extends Command
{
    protected $signature = 'ims:verify {--url=http://localhost:8000}';

    protected $description = 'Verify seeded IMS data, stock calculations, and admin page access';

    public function handle(): int
    {
        $this->components->info('IMS Verification Report');
        $this->newLine();

        $this->table(['Entity', 'Count'], [
            ['Users', User::query()->count()],
            ['SKUs', Sku::query()->count()],
            ['Formulas', RepackagingFormula::query()->count()],
            ['Repackaging Batches', RepackagingBatch::query()->count()],
            ['Material Receipts', MaterialReceipt::query()->count()],
            ['IN Transactions', InventoryTransaction::query()->where('type', 'IN')->count()],
            ['OUT Transactions', InventoryTransaction::query()->where('type', 'OUT')->count()],
        ]);

        $this->newLine();
        $this->components->info('Raw Material Stock');

        $rows = RawMaterial::query()->with('unit')->get()->map(fn (RawMaterial $m): array => [
            $m->name,
            number_format($m->current_stock, 2).' '.$m->unit->symbol,
            number_format($m->minimum_stock, 2).' '.$m->unit->symbol,
            $m->is_low_stock ? 'YES' : 'NO',
        ])->all();

        $this->table(['Material', 'Current', 'Minimum', 'Low?'], $rows);

        $this->newLine();
        $this->components->info('SKUs & Packaging Types');

        $skuRows = Sku::query()->with(['repackagingFormulas.rawMaterial', 'repackagingFormulas.unit'])->get()->map(function (Sku $sku): array {
            $formula = $sku->repackagingFormulas->first();

            return [
                $sku->sku_code,
                $sku->name,
                $sku->packaging_type,
                $formula
                    ? number_format($formula->qty, 4).' '.$formula->unit->symbol.' '.$formula->rawMaterial->name
                    : '—',
            ];
        })->all();

        $this->table(['Code', 'Name', 'Package', 'Formula / Unit'], $skuRows);

        $this->newLine();
        $this->components->info('Repackaging Batches');

        $batchRows = RepackagingBatch::query()->with('sku')->get()->map(fn (RepackagingBatch $b): array => [
            $b->batch_no,
            $b->sku->name,
            $b->quantity,
            $b->repackaged_date?->toDateString(),
        ])->all();

        $this->table(['Batch', 'SKU', 'Qty', 'Date'], $batchRows);

        $baseUrl = rtrim((string) $this->option('url'), '/');
        $admin = User::query()->where('email', 'admin@ims.test')->first();

        if ($admin) {
            $this->newLine();
            $this->components->info('Admin Login: admin@ims.test / password');
        }

        $this->newLine();
        $this->components->info("Admin pages (visit {$baseUrl}/admin after login)");

        foreach ([
            '/admin',
            '/admin/raw-materials',
            '/admin/material-receipts',
            '/admin/stock-ledger',
            '/admin/skus',
            '/admin/repackaging-formulas',
            '/admin/repackaging-batches',
        ] as $path) {
            $this->line("  • {$baseUrl}{$path}");
        }

        try {
            $response = Http::timeout(3)->get($baseUrl);

            if ($response->successful()) {
                $this->newLine();
                $this->components->info("App server responding at {$baseUrl}");
            }
        } catch (\Throwable) {
            $this->newLine();
            $this->components->warn('App server not reachable — run: php artisan serve');
        }

        $stockErrors = [];

        foreach (RawMaterial::query()->with('unit')->get() as $material) {
            $in = (float) $material->inventoryTransactions()->where('type', 'IN')->sum('base_qty');
            $out = (float) $material->inventoryTransactions()->where('type', 'OUT')->sum('base_qty');
            $adjustment = (float) $material->inventoryTransactions()->where('type', 'ADJUSTMENT')->sum('base_qty');
            $expected = $in - $out + $adjustment;

            if (abs($material->current_stock - $expected) >= 0.0001) {
                $stockErrors[] = "{$material->name}: current={$material->current_stock}, expected={$expected}";
            }
        }

        if ($stockErrors !== []) {
            $this->newLine();
            $this->components->error('Stock calculation mismatch!');

            foreach ($stockErrors as $error) {
                $this->line("  • {$error}");
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->success('All stock calculations verified (current stock matches IN − OUT + ADJUSTMENT).');

        return self::SUCCESS;
    }
}
