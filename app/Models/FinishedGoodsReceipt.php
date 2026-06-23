<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\PerformsAtomicInventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishedGoodsReceipt extends Model
{
    use Auditable;
    use HasFactory;
    use PerformsAtomicInventory;

    protected $fillable = [
        'receipt_no',
        'sku_id',
        'supplier_id',
        'cartons_count',
        'packs_per_carton',
        'carton_prefix',
        'received_date',
        'remarks',
        'user_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cartons_count' => 'integer',
            'packs_per_carton' => 'integer',
            'received_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FinishedGoodsReceipt $receipt): void {
            $receipt->user_id ??= auth()->id();

            if ($receipt->packs_per_carton <= 0) {
                $receipt->packs_per_carton = $receipt->sku?->packs_per_carton ?? 20;
            }
        });

        static::created(function (FinishedGoodsReceipt $receipt): void {
            $receipt->loadMissing('sku');
            $prefix = $receipt->carton_prefix ?: $receipt->receipt_no;

            for ($carton = 1; $carton <= $receipt->cartons_count; $carton++) {
                $cartonNo = sprintf('%s-C%03d', $prefix, $carton);

                $batch = FinishedGoodsBatch::query()->create([
                    'finished_goods_receipt_id' => $receipt->id,
                    'repackaging_batch_id' => null,
                    'sku_id' => $receipt->sku_id,
                    'batch_no' => $cartonNo,
                    'quantity' => $receipt->packs_per_carton,
                    'produced_date' => $receipt->received_date,
                ]);

                FinishedGoodsTransaction::query()->create([
                    'sku_id' => $receipt->sku_id,
                    'type' => 'IN',
                    'qty' => $receipt->packs_per_carton,
                    'reference_type' => 'finished_goods_receipt',
                    'reference_id' => $receipt->id,
                    'finished_goods_batch_id' => $batch->id,
                ]);
            }
        });

        static::deleting(function (FinishedGoodsReceipt $receipt): void {
            FinishedGoodsTransaction::query()
                ->where('reference_type', 'finished_goods_receipt')
                ->where('reference_id', $receipt->id)
                ->delete();

            FinishedGoodsBatch::query()
                ->where('finished_goods_receipt_id', $receipt->id)
                ->delete();
        });
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function finishedGoodsBatches(): HasMany
    {
        return $this->hasMany(FinishedGoodsBatch::class);
    }

    public function getTotalPacksAttribute(): int
    {
        return $this->cartons_count * $this->packs_per_carton;
    }
}
