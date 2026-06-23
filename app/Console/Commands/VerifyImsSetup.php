<?php

namespace App\Console\Commands;

use App\Models\Dispatch;
use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsTransaction;
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
            ['FG Batches', FinishedGoodsBatch::query()->count()],
            ['FG Transactions', FinishedGoodsTransaction::query()->count()],
            ['Dispatches', Dispatch::query()->count()],
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
        $admin = User::query()->first();

        if ($admin) {
            $this->newLine();
            $this->components->info("Admin Login: {$admin->email}");
        }

        $this->newLine();
        $this->components->info("Admin pages (visit {$baseUrl}/admin after login)");

        foreach ([
            '/admin',
            '/admin/raw-materials',
            '/admin/material-receipts',
            '/admin/stock-ledger',
            '/admin/finished-goods-ledger',
            '/admin/stock-adjustments',
            '/admin/skus',
            '/admin/repackaging-formulas',
            '/admin/repackaging-batches',
            '/admin/finished-goods-receipts',
            '/admin/dispatches',
            '/admin/stock-dashboard',
            '/admin/audit-logs',
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
                $stockErrors[] = "Raw {$material->name}: current={$material->current_stock}, expected={$expected}";
            }
        }

        foreach (Sku::query()->get() as $sku) {
            $in = (int) $sku->finishedGoodsTransactions()->where('type', 'IN')->sum('qty');
            $out = (int) $sku->finishedGoodsTransactions()->where('type', 'OUT')->sum('qty');
            $adjustment = (int) $sku->finishedGoodsTransactions()->where('type', 'ADJUSTMENT')->sum('qty');
            $expected = $in - $out + $adjustment;

            if ($sku->current_stock !== $expected) {
                $stockErrors[] = "FG {$sku->name}: current={$sku->current_stock}, expected={$expected}";
            }
        }

        $missingFgBatches = RepackagingBatch::query()->whereDoesntHave('finishedGoodsBatch')->count();

        if ($missingFgBatches > 0) {
            $this->newLine();
            $this->components->warn("{$missingFgBatches} repackaging batch(es) missing finished goods records. Run: php artisan ims:backfill-finished-goods");
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

        $this->newLine();
        $this->components->info('Run before go-live: php artisan ims:go-live-check');

        return self::SUCCESS;
    }
}
