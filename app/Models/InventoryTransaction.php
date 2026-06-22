<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['raw_material_id', 'type', 'base_qty', 'reference_type', 'reference_id', 'remarks'];

    public function rawMaterial()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
