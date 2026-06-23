<?php

use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\FinishedGoodsBatch;
use App\Models\FinishedGoodsReceipt;
use App\Models\FinishedGoodsTransaction;
use App\Models\InventoryTransaction;
use App\Models\MaterialReceipt;
use App\Models\RawMaterial;
use App\Models\RepackagingBatch;
use App\Models\RepackagingFormula;
use App\Models\Sku;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->admin = User::query()->firstOrFail();
});

it('seeds sample data for all modules', function (): void {
    expect(User::query()->count())->toBe(1)
        ->and(Sku::query()->count())->toBe(9)
        ->and(RepackagingFormula::query()->count())->toBe(6)
        ->and(RepackagingBatch::query()->count())->toBe(5)
        ->and(MaterialReceipt::query()->count())->toBe(3)
        ->and(FinishedGoodsReceipt::query()->count())->toBe(2)
        ->and(StockAdjustment::query()->count())->toBe(2)
        ->and(InventoryTransaction::query()->where('type', 'IN')->count())->toBe(3)
        ->and(InventoryTransaction::query()->where('type', 'OUT')->count())->toBe(5)
        ->and(InventoryTransaction::query()->where('type', 'ADJUSTMENT')->count())->toBe(1);
});

it('calculates raw material stock after receipts and repackaging', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();
    $creamer = RawMaterial::query()->where('name', 'Creamer Bulk')->first();

    // IN: 500 + 300 coffee | OUT: 200*0.05 + 100*0.10 + 500*0.01(creamer only for creamer) + ...
    // Coffee OUT: 10 + 10 + 12 = 32
    // Creamer OUT: 5 + 10 = 15
    expect($coffee->current_stock)->toBe(766.0)
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
        )->toEqualWithDelta($expectedConsumption, 0.0001);
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

it('creates FG OUT when dispatching finished goods', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'TEST-DISP-001',
        'customer_name' => 'Walk-in Customer',
        'dispatched_date' => now()->toDateString(),
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 10,
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore - 10)
        ->and(
            FinishedGoodsTransaction::query()
                ->where('type', 'OUT')
                ->where('reference_type', 'dispatch')
                ->where('reference_id', $dispatch->id)
                ->first()
                ->qty
        )->toBe(10);
});

it('blocks dispatch when finished goods stock is insufficient', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'TEST-DISP-FAIL',
        'customer_name' => 'Test Customer',
        'dispatched_date' => now()->toDateString(),
    ]);

    expect(fn () => DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 9_999_999,
    ]))->toThrow(ValidationException::class);
});

it('restores FG stock when a dispatch is deleted', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'TEST-DISP-DELETE',
        'customer_name' => 'Test Customer',
        'dispatched_date' => now()->toDateString(),
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 5,
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore - 5);

    $dispatch->delete();

    expect($sku->fresh()->current_stock)->toBe($stockBefore)
        ->and(
            FinishedGoodsTransaction::query()
                ->where('reference_type', 'dispatch')
                ->where('reference_id', $dispatch->id)
                ->exists()
        )->toBeFalse();
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

it('receives chocolate cartons as FG IN with one batch per carton', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CH-STB-500')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $receipt = FinishedGoodsReceipt::query()->create([
        'receipt_no' => 'FG-STB-TEST-001',
        'sku_id' => $sku->id,
        'cartons_count' => 3,
        'packs_per_carton' => 20,
        'carton_prefix' => 'STB',
        'received_date' => now()->toDateString(),
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore + 60)
        ->and($receipt->finishedGoodsBatches()->count())->toBe(3)
        ->and(
            FinishedGoodsBatch::query()
                ->where('finished_goods_receipt_id', $receipt->id)
                ->where('batch_no', 'STB-C001')
                ->first()
                ?->quantity
        )->toBe(20)
        ->and(
            FinishedGoodsTransaction::query()
                ->where('reference_type', 'finished_goods_receipt')
                ->where('reference_id', $receipt->id)
                ->where('type', 'IN')
                ->count()
        )->toBe(3);
});

it('reverses chocolate stock when an FG carton receipt is deleted', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CH-VAN-400')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $receipt = FinishedGoodsReceipt::query()->create([
        'receipt_no' => 'FG-VAN-TEST-DELETE',
        'sku_id' => $sku->id,
        'cartons_count' => 2,
        'packs_per_carton' => 20,
        'received_date' => now()->toDateString(),
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore + 40);

    $receipt->delete();

    expect($sku->fresh()->current_stock)->toBe($stockBefore)
        ->and(FinishedGoodsBatch::query()->where('finished_goods_receipt_id', $receipt->id)->exists())->toBeFalse();
});

it('dispatches chocolate in loose packs and whole cartons', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CH-COC-500')->firstOrFail();
    $stockBefore = $sku->current_stock;
    $carton = FinishedGoodsBatch::query()
        ->where('sku_id', $sku->id)
        ->where('batch_no', 'COC-C002')
        ->firstOrFail();

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'TEST-CH-DISP',
        'customer_name' => 'Chocolate Shop',
        'dispatched_date' => now()->toDateString(),
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 4,
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'finished_goods_batch_id' => $carton->id,
        'quantity' => 20,
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore - 24);
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
    'dispatches' => '/admin/dispatches',
    'fg carton receipts' => '/admin/finished-goods-receipts',
    'audit log' => '/admin/audit-logs',
]);

it('creates material receipt and records raw IN transaction', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->firstOrFail();
    $supplier = Supplier::query()->firstOrFail();
    $kg = Unit::query()->where('symbol', 'kg')->firstOrFail();
    $stockBefore = $coffee->current_stock;

    $receipt = MaterialReceipt::query()->create([
        'raw_material_id' => $coffee->id,
        'supplier_id' => $supplier->id,
        'batch_no' => 'TEST-RECEIPT-001',
        'qty' => 100,
        'unit_id' => $kg->id,
        'received_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($stockBefore + 100)
        ->and(
            InventoryTransaction::query()
                ->where('type', 'IN')
                ->where('reference_type', 'material_receipt')
                ->where('reference_id', $receipt->id)
                ->first()
                ->base_qty
        )->toEqualWithDelta(100.0, 0.0001);

    $receipt->delete();

    expect($coffee->fresh()->current_stock)->toBe($stockBefore);
});

it('creates stock adjustment for finished goods', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $adjustment = StockAdjustment::query()->create([
        'stock_type' => 'finished_goods',
        'sku_id' => $sku->id,
        'direction' => 'decrease',
        'qty' => 3,
        'reason' => 'Damaged packs removed',
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore - 3)
        ->and(
            FinishedGoodsTransaction::query()
                ->where('type', 'ADJUSTMENT')
                ->where('reference_type', 'stock_adjustment')
                ->where('reference_id', $adjustment->id)
                ->exists()
        )->toBeTrue();

    $adjustment->delete();

    expect($sku->fresh()->current_stock)->toBe($stockBefore);
});

it('runs inventory category workflow from receipt to adjustment', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->firstOrFail();
    $supplier = Supplier::query()->firstOrFail();
    $kg = Unit::query()->where('symbol', 'kg')->firstOrFail();
    $stockBefore = $coffee->current_stock;

    $receipt = MaterialReceipt::query()->create([
        'raw_material_id' => $coffee->id,
        'supplier_id' => $supplier->id,
        'batch_no' => 'WF-INV-RECEIPT',
        'qty' => 50,
        'unit_id' => $kg->id,
        'received_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($stockBefore + 50);

    $adjustment = StockAdjustment::query()->create([
        'stock_type' => 'raw_material',
        'raw_material_id' => $coffee->id,
        'direction' => 'increase',
        'qty' => 5,
        'reason' => 'Found extra bags in warehouse',
    ]);

    expect($coffee->fresh()->current_stock)->toBe($stockBefore + 55)
        ->and(
            InventoryTransaction::query()
                ->where('reference_type', 'material_receipt')
                ->where('reference_id', $receipt->id)
                ->exists()
        )->toBeTrue()
        ->and(
            InventoryTransaction::query()
                ->where('reference_type', 'stock_adjustment')
                ->where('reference_id', $adjustment->id)
                ->exists()
        )->toBeTrue();
});

it('runs production category workflow from repackaging batch', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->first();
    $rawBefore = $coffee->current_stock;
    $fgBefore = $sku->current_stock;

    $batch = RepackagingBatch::query()->create([
        'sku_id' => $sku->id,
        'batch_no' => 'WF-PROD-BATCH',
        'quantity' => 40,
        'repackaged_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($rawBefore - 2.0)
        ->and($sku->fresh()->current_stock)->toBe($fgBefore + 40)
        ->and($batch->finishedGoodsBatch)->not->toBeNull()
        ->and(
            InventoryTransaction::query()
                ->where('type', 'OUT')
                ->where('reference_type', 'repackaging_batch')
                ->where('reference_id', $batch->id)
                ->exists()
        )->toBeTrue()
        ->and(
            FinishedGoodsTransaction::query()
                ->where('type', 'IN')
                ->where('reference_type', 'repackaging_batch')
                ->where('reference_id', $batch->id)
                ->exists()
        )->toBeTrue();
});

it('runs sales category workflow from dispatch', function (): void {
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $stockBefore = $sku->current_stock;

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'WF-SALES-DISP',
        'customer_name' => 'Workflow Test Retailer',
        'dispatched_date' => now()->toDateString(),
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 15,
    ]);

    expect($sku->fresh()->current_stock)->toBe($stockBefore - 15)
        ->and(
            FinishedGoodsTransaction::query()
                ->where('type', 'OUT')
                ->where('reference_type', 'dispatch')
                ->where('reference_id', $dispatch->id)
                ->first()
                ->qty
        )->toBe(15);
});

it('runs full end to end workflow across inventory production and sales', function (): void {
    $coffee = RawMaterial::query()->where('name', 'Coffee Bulk')->firstOrFail();
    $sku = Sku::query()->where('sku_code', 'SKU-CG-50')->firstOrFail();
    $supplier = Supplier::query()->firstOrFail();
    $kg = Unit::query()->where('symbol', 'kg')->firstOrFail();

    $rawStart = $coffee->current_stock;
    $fgStart = $sku->current_stock;

    MaterialReceipt::query()->create([
        'raw_material_id' => $coffee->id,
        'supplier_id' => $supplier->id,
        'batch_no' => 'E2E-RAW-IN',
        'qty' => 20,
        'unit_id' => $kg->id,
        'received_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($rawStart + 20);

    RepackagingBatch::query()->create([
        'sku_id' => $sku->id,
        'batch_no' => 'E2E-REPACK',
        'quantity' => 10,
        'repackaged_date' => now()->toDateString(),
    ]);

    expect($coffee->fresh()->current_stock)->toBe($rawStart + 19.5)
        ->and($sku->fresh()->current_stock)->toBe($fgStart + 10);

    $dispatch = Dispatch::query()->create([
        'dispatch_no' => 'E2E-DISPATCH',
        'customer_name' => 'End-to-End Customer',
        'dispatched_date' => now()->toDateString(),
    ]);

    DispatchItem::query()->create([
        'dispatch_id' => $dispatch->id,
        'sku_id' => $sku->id,
        'quantity' => 8,
    ]);

    expect($sku->fresh()->current_stock)->toBe($fgStart + 2)
        ->and(
            InventoryTransaction::query()->where('type', 'IN')->where('reference_type', 'material_receipt')->whereHas('rawMaterial', fn ($q) => $q->where('name', 'Coffee Bulk'))->latest('id')->first()
        )->not->toBeNull()
        ->and(
            FinishedGoodsTransaction::query()->where('type', 'OUT')->where('reference_type', 'dispatch')->where('reference_id', $dispatch->id)->exists()
        )->toBeTrue();
});
