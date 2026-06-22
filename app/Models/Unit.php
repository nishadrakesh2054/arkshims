<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol', 'type', 'conversion_factor', 'is_base'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function skus()
    {
        return $this->hasMany(Sku::class);
    }
}
