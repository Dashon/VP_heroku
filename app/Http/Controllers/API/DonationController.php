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
    public function index()
    {
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
        $request->status = "active";
        $validator = Validator::make($data, [
            'description' => ['nullable', 'string', 'max:512'],
            'stripeToken' => 'required|max:255',
            'amount' => 'required|numeric|min:100',
            'start_date' => 'required|date_format:m/d/Y|after_or_equal:' . $todayDate,
            'type' => "required|in:round_up,once,monthly",
            'stripe_tx' => 'required|max:255',
            'plaid_tx' => 'required|max:255',
        ]);


        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $token = $request->stripeToken;
        $stripeCharge = $current_user->charge($request->amount * 100, ['source' => $token]);

        $donation = Donation::create($data);

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
            'status' => ['string', 'in:' , ['canceled', 'paused', 'active']]
        ]);
        // if($validator->fails()){
        //     return response(['error' => $validator->errors(), 'Validation Error']);
        // }

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
        $donation->delete();

        return response(['message' => 'Deleted']);
    }
}
