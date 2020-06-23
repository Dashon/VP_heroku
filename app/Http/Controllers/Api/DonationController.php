<?php

namespace App\Http\Controllers\API;

use App\Donation;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/donations",
     *     @OA\Response(response="200", description="Display a listing of donations.")
     * )
     */
    public function index()
    {
        $current_user = auth()->user();
        if ($current_user->type === 'donator') {
            response("Not Authorized", 401);
        }

        $donations = Donation::all();
        return response(['donations' => ReponseResource::collection($donations), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $current_user = auth()->user();
        $data = $request->all();
        $todayDate = date('m/d/Y');
        if (!$current_user->hasPaymentMethod()) {
            return response(['message' => 'No payment methods found'], 405);
        }
        $data['status'] = "active";
        $validator = Validator::make($data, [
            'description' => ['nullable', 'string', 'max:512'],
            'stripe_token' => 'required|max:255',
            'amount' => 'required|numeric|min:100',
            'start_date' => 'required|date|after_or_equal:' . $todayDate,
            'type' => "required|in:round_up,once,monthly",
        ]);


        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }


        $billing_cycle_anchor = \Carbon\Carbon::createFromDate($request->start_date);
        $donation = new Donation($data);
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        if ($request->type == 'once') {
            // $current_user->charge($request->amount * 100, ['source' => $request->stripe_token,
            // 'customer'=>$current_user->stripe_id, ]);

              try {
                $stripe->paymentIntents->create([
                  'amount' => $request->amount * 100,
                  'currency' => 'usd',
                  'customer' => $current_user->stripe_id,
                  'payment_method' => $request->stripe_token,
                  'off_session' => true,
                  'confirm' => true,
                ]);
              } catch (\Stripe\Exception\CardException $e) {
                // Error code will be authentication_required if authentication is needed
                echo 'Error code is:' . $e->getError()->code;
                $payment_intent_id = $e->getError()->payment_intent->id;
                $payment_intent = $stripe->paymentIntents->retrieve($payment_intent_id);
              }

        } else if ($request->type == 'monthly') {

            $cent_ammount = round($request->amount / 100) * 100;
            $dollar_amount = $cent_ammount / 100;
            $planId = "price_$dollar_amount" . "_month";
            try {
                $price = $stripe->plans->retrieve($planId);
            } catch (\Throwable $th) {
                $price = $stripe->plans->create([
                    'id' => $planId,
                    'amount' => $cent_ammount,
                    'currency' => 'usd',
                    'interval' => 'month',
                    'product' => 'prod_HW7YzUejCHBuLp',
                ]);
            }

            $stripe->subscriptions->create([
                'customer' => $current_user->stripe_id,
                'items' => [
                  ['price' => $price->id],
                ],
                'default_source'=>$request->stripe_token,
                'billing_cycle_anchor' => $billing_cycle_anchor->timestamp,
                'cancel_at_period_end' => false,
                'prorate' => false,
              ]);
        }


        $current_user->donations()->save($donation);

        return response(['donation' => new ReponseResource($donation), 'message' => 'Created successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function show(Donation $donation)
    {
        $current_user = auth()->user();
        if (($current_user->type != 'admin' || $current_user->type != 'moderator') && $donation->user->id != $current_user->id) {
            response("Not Authorized", 401);
        }

        return response(['donation' => new ReponseResource($donation), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Donation $donation)
    {
        $this->validate($request, [
            'status' => ['string', 'in:', ['canceled', 'paused', 'active']]
        ]);

        $current_user = auth()->user();
        if (($current_user->type != 'admin' || $current_user->type != 'moderator') && $donation->user->id != $current_user->id) {
            response("Not Authorized", 401);
        }

        if ($request->status == 'canceled' && !in_array($this->status, ['active', 'paused'])) {
            return response(['message' => 'donation cannot be canceled'], 405);
        }

        if ($request->status == 'paused' && $this->status != 'active') {
            return response(['message' => 'donation cannot be paused'], 405);
        }

        if ($request->status == 'active' && !$this->status == 'paused') {
            return response(['message' => 'donation cannot be reactivated'], 405);
        }
        $donation->update(['status' => $request->status]);

        return response(['donation' => new ReponseResource($donation), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Donation $donation
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Donation $donation)
    {
        $current_user = auth()->user();
        if (($current_user->type != 'admin' || $current_user->type != 'moderator') && $donation->user->id != $current_user->id) {
            response("Not Authorized", 401);
        }

        $donation->delete();
        return response(['message' => 'Deleted']);
    }
}
