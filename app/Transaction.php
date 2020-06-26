<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Donation;

class Transaction extends Model
{
    protected $fillable = [
        'donation_id', 'stripe_payment_intent', 'transaction_type', 'amount', 'transaction_date', 'status','stripe_fee'
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }
}
