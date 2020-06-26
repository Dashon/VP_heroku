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

    public function index(Request $request)
    {
        $donations = Donation::orderBy('start_date', 'DESC');

        if ($request->status) {
            $donations->where('status', $request->status);
        }
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

        $paginated = $donations->with('user')->with('transactions')->paginate();
        $paginated->getCollection()->each->withSummary();
        return response($paginated, 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Donation  $donation
     * @return \Illuminate\Http\Response
     */
    public function show(Donation $donation)
    {
        $donation->load('user')->load('transactions')->withSummary();
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
        $donation->load('user')->load('transactions')->withSummary();

        return response(['donation' => new ReponseResource($donation), 'message' => 'Updated successfully'], 200);
    }

    public function destroy(Donation $donation)
    {
        return response(['message' => 'Not Implemented'], 501);
    }

    public function store(Donation $donation)
    {
        return response(['message'=>'Not Implemented'], 501);
    }
}
