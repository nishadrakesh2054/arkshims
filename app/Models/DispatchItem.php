<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchItem extends Model
{
    use HasFactory;

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
