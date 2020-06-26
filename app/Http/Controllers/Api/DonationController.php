<?php

namespace App\Http\Controllers\Api;

use App\Donation;
use App\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\Services\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $current_user = auth()->user();
        $donations = $current_user->donations()->with('user');

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
            'stripe_payment_token' => 'required|max:255',
            'amount' => 'required|numeric|min:100',
            'start_date' => 'required|date|after_or_equal:' . $todayDate,
            'type' => "required|in:round_up,once,monthly",
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error'], 400);
        }
        $data['amount'] = round($request->amount / 100) * 100;
        $donation = new Donation($data);
        $current_user->donations()->save($donation);

        if ($donation->type != 'round_up') {
            $cashier = new Cashier();
            $cashier->checkout($donation);
        }
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
        if ($donation->user()->id != $current_user->id) {
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
        if ($donation->user()->id != $current_user->id) {
            response("Not Authorized", 401);
        }

        if ($donation->type != 'monthly') {
            return response(['message' => 'donation cannot be modified'], 405);
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

        $cashier = new Cashier();
        switch ($request->status) {
            case 'active':
                $cashier->reactivateSubsctiption($donation);
                break;
            case 'paused':
                $cashier->pauseSubsctiption($donation);
                break;
            case 'inactive':
                $cashier->cancelSubsctiption($donation);
                break;
        }
        $donation->update(['status' => $request->status]);

        return response(['donation' => new ReponseResource($donation), 'message' => 'Updated successfully'], 200);
    }

    public function destroy(Donation $donation)
    {
        return response(['message'=>'Not Implemented'], 501);
    }


}
