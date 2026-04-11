<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductColorImage;

class ProductColor extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function productColorImages()
    {
        return $this->hasMany(ProductColorImage::class, 'color_id', 'id');
    }
}
