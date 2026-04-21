<?php

namespace App\Mail\Distributor;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DistributorShippingStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;   

    public function __construct(Order $order)
    {
        $this->order = $order->load([
            'distributor',
            'billingAddress',
            'shippingAddress',
            'orderProducts.product',
        ]);
    }

    public function build()
    {
        return $this->subject('Shipping Status Updated - Order #' . $this->order->order_number)
                    ->view('email.orders.distributor_shipping_status');
    }
}