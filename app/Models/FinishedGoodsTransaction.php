<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishedGoodsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku_id',
        'type',
        'qty',
        'reference_type',
        'reference_id',
        'finished_goods_batch_id',
        'remarks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
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
