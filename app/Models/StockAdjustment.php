<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class StockAdjustment extends Model
{
    use Auditable;
    use HasFactory;

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
                    $stock = RawMaterial::query()
                        ->withCurrentStock()
                        ->find($adjustment->raw_material_id)
                        ?->current_stock ?? 0;

                    if ($stock < (float) $adjustment->qty) {
                        throw ValidationException::withMessages([
                            'qty' => "Insufficient raw material stock. Available: {$stock}",
                        ]);
                    }
                }

                if ($adjustment->stock_type === 'finished_goods') {
                    $stock = Sku::query()
                        ->withCurrentStock()
                        ->find($adjustment->sku_id)
                        ?->current_stock ?? 0;

                    if ($stock < (int) $adjustment->qty) {
                        throw ValidationException::withMessages([
                            'qty' => "Insufficient finished goods stock. Available: {$stock}",
                        ]);
                    }
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
