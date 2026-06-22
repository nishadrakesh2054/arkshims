<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'unit_id', 'minimum_stock'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'minimum_stock' => 'float',
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

    public function getCurrentStockAttribute(): float
    {
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

    public static function countLowStock(): int
    {
        return static::query()
            ->get()
            ->filter(fn (self $material): bool => $material->is_low_stock)
            ->count();
    }
}
