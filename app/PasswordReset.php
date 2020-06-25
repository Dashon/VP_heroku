<?php

namespace App;

use App\User;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    protected $fillable = [
        'email', 'token'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
