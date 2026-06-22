<?php

use App\Models\InventoryTransaction;
use App\Models\MaterialReceipt;
use App\Models\RawMaterial;
use App\Models\RepackagingBatch;
use App\Models\RepackagingFormula;
use App\Models\Sku;
use App\Models\StockAdjustment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::query()->where('email', 'admin@ims.test')->first();
});

it('seeds sample data for all modules', function (): void {
    expect(User::query()->count())->toBe(1)
        ->and(Sku::query()->count())->toBe(6)
        ->and(RepackagingFormula::query()->count())->toBe(6)
        ->and(RepackagingBatch::query()->count())->toBe(5)
        ->and(MaterialReceipt::query()->count())->toBe(3)
        ->and(InventoryTransaction::query()->where('type', 'IN')->count())->toBe(3)
        ->and(InventoryTransaction::query()->where('type', 'OUT')->count())->toBe(5);
});

it('calculates raw material stock after receipts and repackaging', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();
    $creamer = RawMaterial::query()->where('name', 'Creamer Bulk')->first();

    // IN: 500 + 300 coffee | OUT: 200*0.05 + 100*0.10 + 500*0.01(creamer only for creamer) + ...
    // Coffee OUT: 10 + 10 + 12 = 32
    // Creamer OUT: 5 + 10 = 15
    expect($coffee->current_stock)->toBe(768.0)
        ->and($creamer->current_stock)->toBe(235.0);
});

it('flags low stock only when below minimum', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();

    expect($coffee->is_low_stock)->toBeFalse();

    $coffee->update(['minimum_stock' => 800]);

    expect($coffee->fresh()->is_low_stock)->toBeTrue();
});

dataset('packaging types', [
    'Pouch 50gm' => ['SKU-CG-50', 0.05, 50],
    'Pouch 100gm' => ['SKU-CG-100', 0.10, 25],
    'Pack 1kg' => ['SKU-CO-1KG', 1.0, 2],
    'Sachet 10gm' => ['SKU-CR-10', 0.01, 100],
    'Jar 200gm' => ['SKU-CR-200', 0.20, 5],
    'Box 12x50gm' => ['SKU-CG-BOX12', 0.60, 3],
]);

it('deducts correct raw material per packaging type on repackaging', function (string $skuCode, float $perUnitBaseQty, int $batchQty): void {
    $sku = Sku::query()->where('sku_code', $skuCode)->with('repackagingFormulas.rawMaterial')->firstOrFail();
    $formula = $sku->repackagingFormulas->first();
    $material = $formula->rawMaterial;
    $stockBefore = $material->current_stock;

    RepackagingBatch::query()->create([
        'sku_id' => $sku->id,
        'batch_no' => 'TEST-'.$skuCode,
        'quantity' => $batchQty,
        'repackaged_date' => now()->toDateString(),
    ]);

    $expectedConsumption = $perUnitBaseQty * $batchQty;

    expect($material->fresh()->current_stock)->toBe($stockBefore - $expectedConsumption)
        ->and(
            InventoryTransaction::query()
                ->where('type', 'OUT')
                ->where('raw_material_id', $material->id)
                ->where('reference_type', 'repackaging_batch')
                ->latest('id')
                ->first()
                ->base_qty
        )->toBe($expectedConsumption);
})->with('packaging types');

it('blocks repackaging when stock is insufficient', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CO-1KG')->firstOrFail();

    expect(fn () => RepackagingBatch::query()->create([
        'sku_id' => $sku->id,
        'batch_no' => 'REPACK-FAIL',
        'quantity' => 99999,
        'repackaged_date' => now()->toDateString(),
    ]))->toThrow(ValidationException::class);
});

it('creates stock adjustment for raw materials', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();
    $stockBefore = $coffee->current_stock;

    $adjustment = StockAdjustment::query()->create([
        'stock_type' => 'raw_material',
        'raw_material_id' => $coffee->id,
        'direction' => 'decrease',
        'qty' => 10,
        'reason' => 'Physical count variance',
    ]);

    expect($coffee->fresh()->current_stock)->toBe($stockBefore - 10)
        ->and(
            InventoryTransaction::query()
                ->where('type', 'ADJUSTMENT')
                ->where('reference_type', 'stock_adjustment')
                ->where('reference_id', $adjustment->id)
                ->exists()
        )->toBeTrue();

    $adjustment->delete();

    expect($coffee->fresh()->current_stock)->toBe($stockBefore);
});

it('restores stock when a repackaging batch is deleted', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();
    $stockBefore = $coffee->current_stock;

    $batch = RepackagingBatch::query()->create([
        'sku_id' => $sku->id,
        'batch_no' => 'DELETE-ME',
        'quantity' => 10,
        'repackaged_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($stockBefore - 0.5);

    $batch->delete();

    expect($coffee->fresh()->current_stock)->toBe($stockBefore);
});

it('allows admin to access key filament pages', function (string $path): void {
    $this->actingAs($this->admin)
        ->get($path)
        ->assertSuccessful();
})->with([
    'dashboard' => '/admin',
    'raw materials' => '/admin/raw-materials',
    'material receipts' => '/admin/material-receipts',
    'stock ledger' => '/admin/stock-ledger',
    'skus' => '/admin/skus',
    'formulas' => '/admin/repackaging-formulas',
    'repackaging batches' => '/admin/repackaging-batches',
    'stock adjustments' => '/admin/stock-adjustments',
    'audit log' => '/admin/audit-logs',
]);
