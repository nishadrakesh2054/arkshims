<?php

namespace App\Models;

use App\Support\StockBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sku extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'brand_id',
        'name',
        'weight',
        'unit_id',
        'packaging_type',
        'sku_code',
        'is_active',
        'minimum_stock',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'is_active' => 'boolean',
            'minimum_stock' => 'integer',
        ];
    }

    /**
     * @param  Builder<Sku>  $query
     * @return Builder<Sku>
     */
    public function scopeWithCurrentStock(Builder $query): Builder
    {
        return $query->addSelect([
            'skus.*',
            'computed_current_stock' => StockBalance::skuStockSubquery(),
        ]);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function repackagingFormulas()
    {
        return $this->hasMany(RepackagingFormula::class);
    }

    public function repackagingBatches()
    {
        return $this->hasMany(RepackagingBatch::class);
    }

    public function finishedGoodsBatches()
    {
        return $this->hasMany(FinishedGoodsBatch::class);
    }

    public function finishedGoodsTransactions()
    {
        return $this->hasMany(FinishedGoodsTransaction::class);
    }

    public function getCurrentStockAttribute(): int
    {
        if (array_key_exists('computed_current_stock', $this->attributes)) {
            return (int) $this->attributes['computed_current_stock'];
        }

        $in = $this->finishedGoodsTransactions()
            ->where('type', 'IN')
            ->sum('qty');

        $out = $this->finishedGoodsTransactions()
            ->where('type', 'OUT')
            ->sum('qty');

        $adjustment = $this->finishedGoodsTransactions()
            ->where('type', 'ADJUSTMENT')
            ->sum('qty');

        return (int) $in - (int) $out + (int) $adjustment;
    }

    public function getIsLowStockAttribute(): bool
    {
        if ($this->minimum_stock <= 0) {
            return false;
        }

        return $this->current_stock < $this->minimum_stock;
    }

    public static function countLowStock(): int
    {
        return StockBalance::skuLowStockCount();
    }
}
