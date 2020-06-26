<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Transaction;
use App\User;

class Donation extends Model
{
    protected $fillable = [
        'user_id', 'description', 'amount', 'start_date', 'type', 'stripe_payment_token', 'status', 'round_up_balance','last_charge_date'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function withSummary()
    {
        $this->total_contribution_amount = $this->transactions()->sum('transactions.amount');
        $this->total_contributions = $this->transactions()->count();
        return $this;
    }
}
