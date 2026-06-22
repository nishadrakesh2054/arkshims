<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialReceipt extends Model
{
    use HasFactory;

    protected $fillable = ['raw_material_id', 'supplier_id', 'batch_no', 'qty', 'unit_id', 'base_qty', 'received_date', 'remarks'];

    protected static function booted(): void
    {
        static::creating(function (MaterialReceipt $receipt): void {
            $receipt->base_qty = (float) $receipt->qty * (float) $receipt->unit->conversion_factor;
        });

        static::created(function (MaterialReceipt $receipt): void {
            InventoryTransaction::create([
                'raw_material_id' => $receipt->raw_material_id,
                'type' => 'IN',
                'base_qty' => $receipt->base_qty,
                'reference_type' => 'material_receipt',
                'reference_id' => $receipt->id,
            ]);
        });
    }

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
