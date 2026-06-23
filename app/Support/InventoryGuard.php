<?php

namespace App\Support;

use App\Models\FinishedGoodsBatch;
use App\Models\RawMaterial;
use App\Models\Sku;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryGuard
{
    public static function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }

    public static function lockedRawMaterialStock(int $rawMaterialId): float
    {
        RawMaterial::query()->whereKey($rawMaterialId)->lockForUpdate()->firstOrFail();

        return StockBalance::rawMaterialStockFor($rawMaterialId);
    }

    public static function lockedSkuStock(int $skuId): int
    {
        Sku::query()->whereKey($skuId)->lockForUpdate()->firstOrFail();

        return StockBalance::skuStockFor($skuId);
    }

    public static function lockedFinishedGoodsBatch(int $batchId): FinishedGoodsBatch
    {
        return FinishedGoodsBatch::query()->whereKey($batchId)->lockForUpdate()->firstOrFail();
    }

    public static function assertRawMaterialStockAvailable(
        int $rawMaterialId,
        float $requiredQty,
        string $field = 'quantity',
    ): void {
        $available = self::lockedRawMaterialStock($rawMaterialId);

        if ($available + 0.0001 < $requiredQty) {
            $material = RawMaterial::query()->find($rawMaterialId);
            $name = $material?->name ?? 'material';

            throw ValidationException::withMessages([
                $field => "Insufficient stock for {$name}. Required: {$requiredQty}, available: {$available}.",
            ]);
        }
    }

    public static function assertSkuStockAvailable(
        int $skuId,
        int $requiredQty,
        string $field = 'quantity',
    ): void {
        $available = self::lockedSkuStock($skuId);

        if ($available < $requiredQty) {
            $sku = Sku::query()->find($skuId);
            $name = $sku?->name ?? 'SKU';

            throw ValidationException::withMessages([
                $field => "Insufficient finished goods stock for {$name}. Required: {$requiredQty}, available: {$available}.",
            ]);
        }
    }

    public static function assertFinishedGoodsBatchStockAvailable(
        int $batchId,
        int $requiredQty,
        string $field = 'quantity',
    ): void {
        $batch = self::lockedFinishedGoodsBatch($batchId);

        if ($batch->available_qty < $requiredQty) {
            throw ValidationException::withMessages([
                $field => "Insufficient batch stock for {$batch->batch_no}. Required: {$requiredQty}, available: {$batch->available_qty}.",
            ]);
        }
    }

    /**
     * @param  array<int, array{sku_id?: int, quantity?: int, finished_goods_batch_id?: int|null}>  $items
     */
    public static function assertDispatchItemsStock(array $items): void
    {
        $requiredBySku = [];

        foreach ($items as $item) {
            $skuId = (int) ($item['sku_id'] ?? 0);
            $qty = (int) ($item['quantity'] ?? 0);

            if ($skuId === 0 || $qty <= 0) {
                continue;
            }

            $requiredBySku[$skuId] = ($requiredBySku[$skuId] ?? 0) + $qty;

            if (! empty($item['finished_goods_batch_id'])) {
                self::assertFinishedGoodsBatchStockAvailable(
                    (int) $item['finished_goods_batch_id'],
                    $qty,
                    'items',
                );
            }
        }

        foreach ($requiredBySku as $skuId => $requiredQty) {
            self::assertSkuStockAvailable($skuId, $requiredQty, 'items');
        }
    }
}
