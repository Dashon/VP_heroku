<?php

namespace App\Services;

use App\Donation;
use App\Transaction;
use App\User;
use Carbon\Carbon;

class Cashier
{
    public function checkout(Donation $donation)
    {
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $this->donation = $donation;
        if ($donation->type == 'once') {
            return $this->chargeOnce();
        }
        if ($donation->type == 'monthly') {
            return $this->subscribe();
        }
        if ($donation->type == 'round_up') {
            return $this->chargeNow();
        }
    }

    public function pauseSubsctiption(Donation $donation)
    {
        $this->stripe->update(
            $donation->stripe_subscription_id,
            ['pause_collection' => ['behavior' => 'void']]
          );
    }

    public function reactivateSubsctiption(Donation $donation)
    {
        $this->stripe->update(
            $donation->stripe_subscription_id,
            ['pause_collection' => '']
          );
    }

    public function cancelSubsctiption(Donation $donation)
    {
        $user = $donation->user();
        $user->subscription($donation->stripe_subscription_id)->cancelNow();
    }

    function chargeOnce()
    {
        $chargeDate = Carbon::createFromDate($this->donation->start_date);

        if ($chargeDate->isFuture()) {
            return $this->subscribe(false);
        } else {
            return $this->chargeNow();
        }
    }

    function subscribe($recurring = true)
    {
        $billing_cycle_anchor = Carbon::createFromDate($this->donation->start_date);
        $dollar_amount = $this->donation->amount / 100;
        $planId = "price_$dollar_amount" . "_month";
        try {
            $price = $this->stripe->plans->retrieve($planId);
        } catch (\Throwable $th) {
            $price = $this->stripe->plans->create([
                'id' => $planId,
                'amount' => $this->donation->amount,
                'currency' => 'usd',
                'interval' => 'month',
                'product' => 'prod_HW7YzUejCHBuLp',
            ]);
        }
        $opts = [
            'customer' => $this->donation->user()->stripe_id,
            'items' => [
                ['price' => $price->id],
            ],
            'default_source' => $this->donation->stripe_payment_token,
            'billing_cycle_anchor' => $billing_cycle_anchor->timestamp,
            'cancel_at_period_end' => false,
            'proration_behavior' => 'none',
            'metadata' => ['donation_id' => $this->donation->id]
        ];
        if (!$recurring) {
            $opts["cancel_at"] = $opts->billing_cycle_anchor + 86400;//cancel subscription the following day
        }

        $subscription = $this->stripe->subscriptions->create($opts);
        $this->donation->update(['stripe_subscription_id' => $subscription->id]);
        return $this->donation;
    }

    function chargeNow()
    {
        try {
            $payment_intent =  $this->stripe->paymentIntents->create([
                'amount' => $this->donation->amount,
                'currency' => 'usd',
                'customer' => $this->donation->user()->stripe_id,
                'payment_method' => $this->donation->stripe_payment_token,
                'setup_future_usage' => 'off_session',
                'off_session' => true,
                'confirm' => true,
                'metadata' => ['donation_id' => $this->donation->id]
            ]);
            $transaction = new Transaction([
                'transaction_type' => 'CHARGE',
                'status' => 'pending',
                'stripe_payment_intent' => $payment_intent->id,
                'transaction_date' =>  Carbon::today()->format('m/d/Y'),
                'amount' => $this->donation->amount,
            ]);
            $this->donation->transactions()->save($transaction);
        } catch (\Stripe\Exception\CardException $e) {
            // Error code will be authentication_required if authentication is needed
            return ['error' => $e->getError()->code];
        }
        return $this->donation;
    }
}
