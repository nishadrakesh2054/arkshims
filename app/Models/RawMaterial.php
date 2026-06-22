<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\StockBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = ['name', 'unit_id', 'minimum_stock', 'cost_per_unit'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_stock' => 'float',
            'cost_per_unit' => 'float',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function materialReceipts()
    {
        return $this->hasMany(MaterialReceipt::class);
    }

    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function repackagingFormulas()
    {
        return $this->hasMany(RepackagingFormula::class);
    }

    /**
     * @param  Builder<RawMaterial>  $query
     * @return Builder<RawMaterial>
     */
    public function scopeWithCurrentStock(Builder $query): Builder
    {
        return $query->addSelect([
            'raw_materials.*',
            'computed_current_stock' => StockBalance::rawMaterialStockSubquery(),
        ]);
    }

    public function getCurrentStockAttribute(): float
    {
        if (array_key_exists('computed_current_stock', $this->attributes)) {
            return (float) $this->attributes['computed_current_stock'];
        }

        $in = $this->inventoryTransactions()
            ->where('type', 'IN')
            ->sum('base_qty');

        $out = $this->inventoryTransactions()
            ->where('type', 'OUT')
            ->sum('base_qty');

        $adjustment = $this->inventoryTransactions()
            ->where('type', 'ADJUSTMENT')
            ->sum('base_qty');

        return (float) $in - (float) $out + (float) $adjustment;
    }

    public function getIsLowStockAttribute(): bool
    {
        if ($this->minimum_stock <= 0) {
            return false;
        }

        return $this->current_stock < $this->minimum_stock;
    }

    public function getStockValueAttribute(): float
    {
        return $this->current_stock * (float) $this->cost_per_unit;
    }

    public static function countLowStock(): int
    {
        return StockBalance::rawMaterialLowStockCount();
    }

    public static function totalStockValue(): float
    {
        return (float) static::query()
            ->withCurrentStock()
            ->get(['id', 'cost_per_unit', 'computed_current_stock'])
            ->sum(fn (self $material): float => $material->current_stock * (float) $material->cost_per_unit);
    }
}
