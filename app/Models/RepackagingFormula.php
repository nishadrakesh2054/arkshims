<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepackagingFormula extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku_id',
        'raw_material_id',
        'qty',
        'unit_id',
        'base_qty',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'qty' => 'float',
            'base_qty' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RepackagingFormula $formula): void {
            $formula->base_qty = (float) $formula->qty * (float) $formula->unit->conversion_factor;
        });

        static::updating(function (RepackagingFormula $formula): void {
            if ($formula->isDirty(['qty', 'unit_id'])) {
                $formula->base_qty = (float) $formula->qty * (float) $formula->unit->conversion_factor;
            }
        });
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
