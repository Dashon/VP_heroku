<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\User;

class BankAccount extends Model
{
     protected $fillable = [
        'routing_number','account_number','plaid_id', 'plaid_secrete', 'stripe_token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccountTransactions()
    {
        return $this->hasMany(BankAccountTransaction::class);
    }


}
