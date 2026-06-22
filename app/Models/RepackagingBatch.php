<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class RepackagingBatch extends Model
{
    use HasFactory;

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
                ->with('rawMaterial')
                ->get();

            if ($formulas->isEmpty()) {
                throw ValidationException::withMessages([
                    'sku_id' => ['No repackaging formula exists for the selected SKU.'],
                ]);
            }

            foreach ($formulas as $formula) {
                $consumption = (float) $formula->base_qty * (int) $batch->quantity;

                if ($formula->rawMaterial->current_stock < $consumption) {
                    throw ValidationException::withMessages([
                        'quantity' => [
                            "Insufficient stock for {$formula->rawMaterial->name}. Required: {$consumption}, available: {$formula->rawMaterial->current_stock}.",
                        ],
                    ]);
                }
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
        });

        static::deleting(function (RepackagingBatch $batch): void {
            InventoryTransaction::query()
                ->where('reference_type', 'repackaging_batch')
                ->where('reference_id', $batch->id)
                ->delete();
        });
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
