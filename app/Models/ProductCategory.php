<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    public function skus()
    {
        return $this->hasMany(Sku::class);
    }
}
