<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\PerformsAtomicInventory;
use App\Support\InventoryGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use Auditable;
    use HasFactory;
    use PerformsAtomicInventory;

    protected $fillable = [
        'stock_type',
        'raw_material_id',
        'sku_id',
        'direction',
        'qty',
        'reason',
        'remarks',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StockAdjustment $adjustment): void {
            $adjustment->user_id ??= auth()->id();

            if ($adjustment->direction === 'decrease') {
                if ($adjustment->stock_type === 'raw_material') {
                    InventoryGuard::assertRawMaterialStockAvailable(
                        (int) $adjustment->raw_material_id,
                        (float) $adjustment->qty,
                        'qty',
                    );
                }

                if ($adjustment->stock_type === 'finished_goods') {
                    InventoryGuard::assertSkuStockAvailable(
                        (int) $adjustment->sku_id,
                        (int) $adjustment->qty,
                        'qty',
                    );
                }
            }
        });

        static::created(function (StockAdjustment $adjustment): void {
            $signedQty = $adjustment->direction === 'increase'
                ? (float) $adjustment->qty
                : -(float) $adjustment->qty;

            if ($adjustment->stock_type === 'raw_material') {
                InventoryTransaction::query()->create([
                    'raw_material_id' => $adjustment->raw_material_id,
                    'type' => 'ADJUSTMENT',
                    'base_qty' => $signedQty,
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $adjustment->id,
                    'remarks' => $adjustment->reason,
                ]);
            }

            if ($adjustment->stock_type === 'finished_goods') {
                FinishedGoodsTransaction::query()->create([
                    'sku_id' => $adjustment->sku_id,
                    'type' => 'ADJUSTMENT',
                    'qty' => (int) $signedQty,
                    'reference_type' => 'stock_adjustment',
                    'reference_id' => $adjustment->id,
                    'remarks' => $adjustment->reason,
                ]);
            }
        });

        static::deleting(function (StockAdjustment $adjustment): void {
            if ($adjustment->stock_type === 'raw_material') {
                InventoryTransaction::query()
                    ->where('reference_type', 'stock_adjustment')
                    ->where('reference_id', $adjustment->id)
                    ->delete();
            }

            if ($adjustment->stock_type === 'finished_goods') {
                FinishedGoodsTransaction::query()
                    ->where('reference_type', 'stock_adjustment')
                    ->where('reference_id', $adjustment->id)
                    ->delete();
            }
        });
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
