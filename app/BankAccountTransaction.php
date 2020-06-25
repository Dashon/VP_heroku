<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankAccountTransaction extends Model
{
    protected $fillable = [
        'merchant','amount','round_up_amount', 'transaction_date'
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

}
