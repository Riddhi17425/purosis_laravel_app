<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminPurchaseOrderMail extends Mailable
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
        return $this->subject('New Purchase Order Received - Order #' . $this->order->order_number)
                    ->view('email.orders.admin_purchase_order');
    }
}