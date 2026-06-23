<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\PerformsAtomicInventory;
use App\Support\InventoryGuard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class RepackagingBatch extends Model
{
    use Auditable;
    use HasFactory;
    use PerformsAtomicInventory;

    protected $fillable = [
        'sku_id',
        'batch_no',
        'quantity',
        'repackaged_date',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'repackaged_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RepackagingBatch $batch): void {
            $formulas = RepackagingFormula::query()
                ->where('sku_id', $batch->sku_id)
                ->get();

            if ($formulas->isEmpty()) {
                throw ValidationException::withMessages([
                    'sku_id' => ['No repackaging formula exists for the selected SKU.'],
                ]);
            }

            foreach ($formulas as $formula) {
                $consumption = (float) $formula->base_qty * (int) $batch->quantity;

                InventoryGuard::assertRawMaterialStockAvailable(
                    $formula->raw_material_id,
                    $consumption,
                    'quantity',
                );
            }
        });

        static::created(function (RepackagingBatch $batch): void {
            $formulas = RepackagingFormula::query()
                ->where('sku_id', $batch->sku_id)
                ->get();

            foreach ($formulas as $formula) {
                InventoryTransaction::create([
                    'raw_material_id' => $formula->raw_material_id,
                    'type' => 'OUT',
                    'base_qty' => (float) $formula->base_qty * (int) $batch->quantity,
                    'reference_type' => 'repackaging_batch',
                    'reference_id' => $batch->id,
                ]);
            }

            $finishedGoodsBatch = FinishedGoodsBatch::query()->create([
                'repackaging_batch_id' => $batch->id,
                'sku_id' => $batch->sku_id,
                'batch_no' => $batch->batch_no,
                'quantity' => $batch->quantity,
                'produced_date' => $batch->repackaged_date,
            ]);

            FinishedGoodsTransaction::query()->create([
                'sku_id' => $batch->sku_id,
                'type' => 'IN',
                'qty' => $batch->quantity,
                'reference_type' => 'repackaging_batch',
                'reference_id' => $batch->id,
                'finished_goods_batch_id' => $finishedGoodsBatch->id,
            ]);
        });

        static::deleting(function (RepackagingBatch $batch): void {
            InventoryTransaction::query()
                ->where('reference_type', 'repackaging_batch')
                ->where('reference_id', $batch->id)
                ->delete();

            FinishedGoodsTransaction::query()
                ->where('reference_type', 'repackaging_batch')
                ->where('reference_id', $batch->id)
                ->delete();

            FinishedGoodsBatch::query()
                ->where('repackaging_batch_id', $batch->id)
                ->delete();
        });
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function finishedGoodsBatch()
    {
        return $this->hasOne(FinishedGoodsBatch::class);
    }
}
