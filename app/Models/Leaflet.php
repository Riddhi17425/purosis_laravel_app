<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leaflet extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'leaflets';
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Product::class, 'category', 'id');
    }
}