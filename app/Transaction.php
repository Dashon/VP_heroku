<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Donation;
class Transaction extends Model
{
    protected $fillable = [
        'donation_id', 'stripe_tx', 'plaid_tx'
    ];

    public function donation()
    {
        return $this->belongsTo(Donation::class);
    }

}
