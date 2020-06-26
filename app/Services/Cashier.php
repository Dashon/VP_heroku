<?php

namespace App\Services;

use App\Donation;
use App\Notifications\Donation\CanceledRecurringDonation;
use App\Notifications\Donation\NewOneTimeDonation;
use App\Notifications\Donation\NewRecurringDonation;
use App\Notifications\Donation\NewRoundUpDonation;
use App\Notifications\Donation\PausedRecurringDonation;
use App\Notifications\Donation\ResumedRecurringDonation;
use App\Transaction;
use App\User;
use Carbon\Carbon;

class Cashier
{
    protected $donation;
    public function checkout(Donation $donation)
    {
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $user = $donation->user()->firstOrFail();

        if ($donation->type == 'once') {
            return $this->chargeOnce($donation);
        }
        if ($donation->type == 'monthly') {
            return $this->subscribe($donation);
        }
        if ($donation->type == 'round_up') {
            $donation = $this->chargeNow($donation);
            $user->notify(new NewRoundUpDonation());
            return $donation;
        }
    }

    public function pauseSubsctiption(Donation $donation)
    {
        $this->stripe->update(
            $donation->stripe_subscription_id,
            ['pause_collection' => ['behavior' => 'void']]
        );
        $user = $donation->user()->firstOrFail();

        $user->notify(new PausedRecurringDonation());
    }

    public function reactivateSubsctiption(Donation $donation)
    {
        $this->stripe->update(
            $donation->stripe_subscription_id,
            ['pause_collection' => '']
        );
        $user = $donation->user()->firstOrFail();

        $user->notify(new ResumedRecurringDonation());
    }

    public function cancelSubsctiption(Donation $donation)
    {
        $user = $donation->user();
        $user->subscription($donation->stripe_subscription_id)->cancelNow();
        $donation->user()->notify(new CanceledRecurringDonation());
    }

    function chargeOnce(Donation $donation)
    {
        $chargeDate = Carbon::createFromDate($donation->start_date);
        $user = $donation->user()->firstOrFail();

        if ($chargeDate->isFuture()) {
            return $this->subscribe($donation,false);
        } else {
            return $this->chargeNow($donation);
        }
        $user->notify(new NewOneTimeDonation());
    }

    function subscribe(Donation $donation, $recurring = true)
    {
        $user = $donation->user()->firstOrFail();
        $billing_cycle_anchor = Carbon::createFromDate($donation->start_date);
        $dollar_amount = $donation->amount / 100;
        $planId = "price_$dollar_amount" . "_month";
        try {
            $price = $this->stripe->plans->retrieve($planId);
        } catch (\Throwable $th) {
            $price = $this->stripe->plans->create([
                'id' => $planId,
                'amount' => $donation->amount,
                'currency' => 'usd',
                'interval' => 'month',
                'product' => 'prod_HW7YzUejCHBuLp',
            ]);
        }
        $opts = [
            'customer' => $user->stripe_id,
            'items' => [
                ['price' => $price->id],
            ],
            'default_source' => $donation->stripe_payment_token,
            'billing_cycle_anchor' => $billing_cycle_anchor->timestamp,
            'proration_behavior' => 'none',
            'metadata' => ['donation_id' => $donation->id]
        ];
        if (!$recurring) {
            $opts["cancel_at"] = $opts['billing_cycle_anchor'] + 86400; //cancel subscription the following day
        }

        $subscription = $this->stripe->subscriptions->create($opts);
        $donation->update(['stripe_subscription_id' => $subscription->id]);

        $user->notify(new NewRecurringDonation());

        return $donation;
    }

    function chargeNow(Donation $donation)
    {
        $user = $donation->user()->firstOrFail();
        try {
            $payment_intent =  $this->stripe->paymentIntents->create([
                'amount' => $donation->amount,
                'currency' => 'usd',
                'customer' => $user->stripe_id,
                'payment_method' => $donation->stripe_payment_token,
                'setup_future_usage' => 'off_session',
                'confirm' => true,
                'metadata' => ['donation_id' => $donation->id]
            ]);
            $transaction = new Transaction([
                'transaction_type' => 'CHARGE',
                'status' => 'pending',
                'stripe_payment_intent' => $payment_intent->id,
                'transaction_date' =>  Carbon::today(),
                'amount' => $donation->amount,
            ]);
            $donation->transactions()->save($transaction);
        } catch (\Stripe\Exception\CardException $e) {
            // Error code will be authentication_required if authentication is needed
            return ['error' => $e->getError()->code];
        }
        return $donation;
    }
}
