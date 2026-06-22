<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Dispatch extends Model
{
    use Auditable;
    use HasFactory;

    protected $fillable = [
        'dispatch_no',
        'customer_name',
        'dispatched_date',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispatched_date' => 'date',
        ];
    }

    public function items()
    {
        return $this->hasMany(DispatchItem::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (Dispatch $dispatch): void {
            FinishedGoodsTransaction::query()
                ->where('reference_type', 'dispatch')
                ->where('reference_id', $dispatch->id)
                ->delete();
        });
    }

    /**
     * @param  array<int, array{sku_id: int, quantity: int, finished_goods_batch_id?: int|null}>  $items
     */
    public static function validateStockForItems(array $items): void
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
                $batch = FinishedGoodsBatch::query()->find($item['finished_goods_batch_id']);

                if ($batch && $batch->available_qty < $qty) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient batch stock for {$batch->batch_no}. Required: {$qty}, available: {$batch->available_qty}.",
                        ],
                    ]);
                }
            }
        }

        foreach ($requiredBySku as $skuId => $requiredQty) {
            $sku = Sku::query()->findOrFail($skuId);

            if ($sku->current_stock < $requiredQty) {
                throw ValidationException::withMessages([
                    'items' => [
                        "Insufficient finished goods stock for {$sku->name}. Required: {$requiredQty}, available: {$sku->current_stock}.",
                    ],
                ]);
            }
        }
    }
}
