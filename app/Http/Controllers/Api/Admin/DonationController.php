<?php

namespace App\Http\Controllers\Api\Admin;

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
     * @OA\Get(
     *     path="/donation",
     *     @OA\Response(response="200", description="Display a listing of donations.")
     * )
     */
    public function index(Request $request)
    {
        $status = $request->status ?? 'succeeded';
        $donations = Donation::where('status', $status);
        if ($request->start_date && $request->end_date) {
            $donations->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }
        if ($request->keyword) {
            $donations->where('id', 'like', '%' . $request->keyword . '%');
        }
        if ($request->type) {
            $donations->where('type', $request->type);
        }
        $donations->whereHas('user', function ($userQuery) use ($request) {
            if ($request->only_beneficiary) {
                $userQuery->where('isBeneficiary', true);
            }
            if ($request->keyword) {
                $userQuery->where('email', 'like', '%' . $request->keyword . '%')
                    ->orWhere('city', 'like', '%' . $request->keyword . '%')
                    ->orWhere('state', 'like', '%' . $request->keyword . '%');
            }
        });
        $donations->paginate();
        $result = $donations->get()
            ->each(function (Donation $donation) {
                $user = $donation->user()->firstOrFail();
                return [
                    'id' => $donation->id,
                    'status' => $donation->status,
                    'date_time' => $donation->start_date,
                    'last_charge_date' => $donation->last_charge_date,
                    'amount' => $donation->amount,
                    'type' => $donation->type,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'city' => $user->city,
                    'state' => $user->state
                ];
            });
        return response($result, 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function show(Donation $donation)
    {
        $donation->with(['user']);
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
}
