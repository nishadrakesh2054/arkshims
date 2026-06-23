<?php

namespace App\Models;

use App\Models\Concerns\PerformsAtomicInventory;
use App\Support\InventoryGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchItem extends Model
{
    use HasFactory;
    use PerformsAtomicInventory;

    protected $fillable = [
        'dispatch_id',
        'sku_id',
        'finished_goods_batch_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DispatchItem $item): void {
            $existingQty = 0;

            if ($item->dispatch_id) {
                $existingQty = (int) DispatchItem::query()
                    ->where('dispatch_id', $item->dispatch_id)
                    ->where('sku_id', $item->sku_id)
                    ->sum('quantity');
            }

            $requiredQty = $existingQty + (int) $item->quantity;

            if (! empty($item->finished_goods_batch_id)) {
                InventoryGuard::assertFinishedGoodsBatchStockAvailable(
                    (int) $item->finished_goods_batch_id,
                    (int) $item->quantity,
                );
            }

            InventoryGuard::assertSkuStockAvailable((int) $item->sku_id, $requiredQty);
        });

        static::created(function (DispatchItem $item): void {
            FinishedGoodsTransaction::query()->create([
                'sku_id' => $item->sku_id,
                'type' => 'OUT',
                'qty' => $item->quantity,
                'reference_type' => 'dispatch',
                'reference_id' => $item->dispatch_id,
                'finished_goods_batch_id' => $item->finished_goods_batch_id,
            ]);
        });
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function finishedGoodsBatch(): BelongsTo
    {
        return $this->belongsTo(FinishedGoodsBatch::class);
    }
}
