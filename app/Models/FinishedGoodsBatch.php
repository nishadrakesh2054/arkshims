<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishedGoodsBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'repackaging_batch_id',
        'sku_id',
        'batch_no',
        'quantity',
        'produced_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'produced_date' => 'date',
        ];
    }

    public function repackagingBatch(): BelongsTo
    {
        return $this->belongsTo(RepackagingBatch::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function dispatchItems(): HasMany
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function getDispatchedQtyAttribute(): int
    {
        return (int) $this->dispatchItems()->sum('quantity');
    }

    public function getAvailableQtyAttribute(): int
    {
        return max(0, (int) $this->quantity - $this->dispatched_qty);
    }
}
