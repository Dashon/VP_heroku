<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Transaction;
class Donation extends Model
{
    protected $fillable = [
        'user_id', 'description', 'amount', 'start_date', 'type', 'stript_tx', 'plaid_tx', 'status'
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
