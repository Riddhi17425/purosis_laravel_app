<?php

namespace App\Mail\Distributor;

use App\Models\PromotionalStockTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PromotionalStockOutwardDistributorSendMail extends Mailable
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
        return $this->subject('Promotional Stock Issued - ' . ($this->transaction->serial_no ?? ('OUT-' . $this->transaction->id)))
                    ->view('email.distributor.promotional_stock_outward_distributor');
    }
}