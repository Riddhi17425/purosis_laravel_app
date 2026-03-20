<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $year = now()->year;
            $prefix = "ORD-{$year}-";

            // Find the highest sequence number used this year
            $last = DB::table('orders')
                ->where('order_number', 'like', $prefix . '%')
                ->orderByDesc('id')
                ->value('order_number');

            if ($last) {
                $sequence = (int) substr($last, strlen($prefix)) + 1;
            } else {
                $sequence = 1;
            }

            $order->order_number = $prefix . str_pad($sequence, 6, '0', STR_PAD_LEFT);
        });
    }

    public function distributor()
    {
        return $this->belongsTo(Distributor::class, 'distributor_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class, 'order_id');
    }
}
