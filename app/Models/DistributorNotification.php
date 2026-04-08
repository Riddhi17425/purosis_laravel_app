<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributorNotification extends Model
{
    protected $guarded = [];

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
