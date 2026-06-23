<?php

namespace App\Support;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class StockBalance
{
    /**
     * Raw material stock subquery: IN − OUT + ADJUSTMENT
     */
    public static function rawMaterialStockSubquery(): QueryBuilder
    {
        return DB::table('inventory_transactions')
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type = 'IN' THEN base_qty
                WHEN type = 'OUT' THEN -base_qty
                WHEN type = 'ADJUSTMENT' THEN base_qty
                ELSE 0
            END), 0)")
            ->whereColumn('inventory_transactions.raw_material_id', 'raw_materials.id');
    }

    /**
     * Finished goods stock subquery: IN − OUT + ADJUSTMENT
     */
    public static function skuStockSubquery(): QueryBuilder
    {
        return DB::table('finished_goods_transactions')
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type = 'IN' THEN qty
                WHEN type = 'OUT' THEN -qty
                WHEN type = 'ADJUSTMENT' THEN qty
                ELSE 0
            END), 0)")
            ->whereColumn('finished_goods_transactions.sku_id', 'skus.id');
    }

    public static function rawMaterialStockSql(): string
    {
        return '('.self::rawMaterialStockSubquery()->toSql().')';
    }

    public static function rawMaterialStockFor(int $rawMaterialId): float
    {
        return (float) DB::table('inventory_transactions')
            ->where('raw_material_id', $rawMaterialId)
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type = 'IN' THEN base_qty
                WHEN type = 'OUT' THEN -base_qty
                WHEN type = 'ADJUSTMENT' THEN base_qty
                ELSE 0
            END), 0) as stock")
            ->value('stock');
    }

    public static function skuStockFor(int $skuId): int
    {
        return (int) DB::table('finished_goods_transactions')
            ->where('sku_id', $skuId)
            ->selectRaw("COALESCE(SUM(CASE
                WHEN type = 'IN' THEN qty
                WHEN type = 'OUT' THEN -qty
                WHEN type = 'ADJUSTMENT' THEN qty
                ELSE 0
            END), 0) as stock")
            ->value('stock');
    }

    public static function rawMaterialLowStockCount(): int
    {
        return (int) DB::table('raw_materials')
            ->where('minimum_stock', '>', 0)
            ->whereRaw(
                '(SELECT COALESCE(SUM(CASE
                    WHEN type = \'IN\' THEN base_qty
                    WHEN type = \'OUT\' THEN -base_qty
                    WHEN type = \'ADJUSTMENT\' THEN base_qty
                    ELSE 0
                END), 0) FROM inventory_transactions WHERE inventory_transactions.raw_material_id = raw_materials.id) < minimum_stock'
            )
            ->count();
    }

    public static function skuLowStockCount(): int
    {
        return (int) DB::table('skus')
            ->where('minimum_stock', '>', 0)
            ->where('is_active', true)
            ->whereRaw(
                '(SELECT COALESCE(SUM(CASE
                    WHEN type = \'IN\' THEN qty
                    WHEN type = \'OUT\' THEN -qty
                    WHEN type = \'ADJUSTMENT\' THEN qty
                    ELSE 0
                END), 0) FROM finished_goods_transactions WHERE finished_goods_transactions.sku_id = skus.id) < minimum_stock'
            )
            ->count();
    }
}
