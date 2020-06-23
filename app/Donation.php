<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Transaction;
use App\User;

class Donation extends Model
{
    protected $fillable = [
        'user_id', 'description', 'amount', 'start_date', 'type', 'stripe_token', 'status'
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
