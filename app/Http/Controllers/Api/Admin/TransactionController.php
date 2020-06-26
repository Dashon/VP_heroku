<?php

namespace App\Http\Controllers\Api\Admin;

use App\Donation;
use App\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\Services\Cashier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $status = $request->status ?? 'pending';
        $transactions = Transaction::where('status', $status);

        if ($request->start_date && $request->end_date) {
            $transactions->whereBetween('transaction_date', [$request->start_date, $request->end_date]);
        }
        $transactions->whereHas('donation', function ($donationQuery) use ($request) {
            if ($request->keyword) {
                $donationQuery->where('id', 'like', '%' . $request->keyword . '%');
            }
            if ($request->type) {
                $donationQuery->where('type', $request->type);
            }
            $donationQuery->whereHas('user', function ($userQuery) use ($request) {
                if ($request->only_beneficiary) {
                    $userQuery->where('isBeneficiary', true);
                }
                if ($request->keyword) {
                    $userQuery->where('email', 'like', '%' . $request->keyword . '%')
                        ->orWhere('city', 'like', '%' . $request->keyword . '%')
                        ->orWhere('state', 'like', '%' . $request->keyword . '%');
                }
            });
        });

        $paginated = $transactions->with('donation.user')->paginate();
        return response($paginated, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        $transaction->load('donation.user');
        return response(['transaction' => new ReponseResource($transaction), 'message' => 'Retrieved successfully'], 200);
    }
    public function store(Transaction $transaction)
    {
        return response(['message' => 'Not Implemented'], 501);
    }
    public function destroy(Transaction $transaction)
    {
        return response(['message' => 'Not Implemented'], 501);
    }
    public function update(Transaction $transaction)
    {
        return response(['message' => 'Not Implemented'], 501);
    }
}
