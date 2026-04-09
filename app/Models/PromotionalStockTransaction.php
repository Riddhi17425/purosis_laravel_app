<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionalStockTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(PromotionalStock::class, 'item_id');
    }

    public function recipient()
    {
        return $this->belongsTo(Distributor::class, 'recipient_id');
    }
}

