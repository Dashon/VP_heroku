<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Laravel\Passport\HasApiTokens;
use Laravel\Cashier\Billable;
use App\BankAccount;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, Billable,CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'terms', 'privacy', 'policy', 'type', 'active', 'plaid_id', 'stripe_id', 'is_beneficiary'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'terms', 'privacy', 'policy',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'privacy' => 'datetime',
        'policy' => 'datetime',
        'terms' => 'datetime',
    ];

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'user_roles');
    // }

    public function isModerator($object)
    {
        return $this->type == "Moderator";
    }

    public function isAdmin($object)
    {
        return $this->type == "Admin";
    }

    public function paymentSources()
    {
        return $this->paymentMethods();

    }

    public function donations()
    {
        return $this->hasMany(Donation::class);
    }
    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class);
    }
}
