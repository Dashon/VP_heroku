<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Laravel\Cashier\Billable;
use App\BankAccount;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens, Billable;

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
        $payment_sources = array();
        if ($this->hasPaymentMethod()) {
            foreach ($this->paymentMethods() as $card) {
                array_push($payment_sources, [
                    'id' => $card->id,
                    'type' => ucwords($card->card->brand),
                    'last_four' => $card->card->last4,
                    'stripe_id' => $card->id
                ]);
            }

            foreach ($this->bankAccounts() as $bankAccount) {
                array_push($payment_sources, [
                    'id' => $bankAccount->id,
                    'type' => "Bank Account",
                    'last_four' => $bankAccount->last4,
                    'stripe_id' => $bankAccount->stripe_id
                ]);
            }
        }
        return $payment_sources;
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
