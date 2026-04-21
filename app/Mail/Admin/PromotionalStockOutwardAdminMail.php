<?php

namespace App\Mail\Admin;

use App\Models\PromotionalStockTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PromotionalStockOutwardAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public $transaction; 

    public function __construct(PromotionalStockTransaction $transaction)
    {
        $this->transaction = $transaction->loadMissing([
            'item',
            'recipient',
        ]);
    }

    public function build()
    {
        return $this->subject('Promotional Stock Outward - ' . ($this->transaction->serial_no ?? ('OUT-' . $this->transaction->id)))
                    ->view('email.admin.promotional_stock_outward_admin');
    }
}