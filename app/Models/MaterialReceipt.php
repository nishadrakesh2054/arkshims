<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\PerformsAtomicInventory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialReceipt extends Model
{
    use Auditable;
    use HasFactory;
    use PerformsAtomicInventory;

    protected $fillable = ['raw_material_id', 'supplier_id', 'batch_no', 'qty', 'unit_id', 'base_qty', 'received_date', 'remarks'];

    protected static function booted(): void
    {
        static::creating(function (MaterialReceipt $receipt): void {
            $receipt->loadMissing('unit');
            $receipt->base_qty = (float) $receipt->qty * (float) $receipt->unit->conversion_factor;
        });

        static::created(function (MaterialReceipt $receipt): void {
            InventoryTransaction::query()->create([
                'raw_material_id' => $receipt->raw_material_id,
                'type' => 'IN',
                'base_qty' => $receipt->base_qty,
                'reference_type' => 'material_receipt',
                'reference_id' => $receipt->id,
            ]);
        });

        static::updating(function (MaterialReceipt $receipt): void {
            if ($receipt->isDirty(['qty', 'unit_id'])) {
                $receipt->loadMissing('unit');
                $receipt->base_qty = (float) $receipt->qty * (float) $receipt->unit->conversion_factor;
            }
        });

        static::updated(function (MaterialReceipt $receipt): void {
            InventoryTransaction::query()
                ->where('reference_type', 'material_receipt')
                ->where('reference_id', $receipt->id)
                ->update([
                    'raw_material_id' => $receipt->raw_material_id,
                    'base_qty' => $receipt->base_qty,
                ]);
        });

        static::deleting(function (MaterialReceipt $receipt): void {
            InventoryTransaction::query()
                ->where('reference_type', 'material_receipt')
                ->where('reference_id', $receipt->id)
                ->delete();
        });
    }

    protected function shouldSaveInventoryAtomically(): bool
    {
        if (! $this->exists) {
            return true;
        }

        return $this->isDirty(['qty', 'unit_id', 'raw_material_id']);
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

    public function inventoryTransaction()
    {
        return $this->hasOne(InventoryTransaction::class, 'reference_id')
            ->where('reference_type', 'material_receipt');
    }
}
