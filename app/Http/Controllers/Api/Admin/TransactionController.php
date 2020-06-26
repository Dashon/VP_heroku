<?php

namespace App\Http\Controllers\Api\Admin;

use App\Donation;
use App\Transaction;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\Services\Cashier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/transaction",
     *     @OA\Response(response="200", description="Display a listing of transactions.")
     * )
     */
    public function index(Request $request)
    {
        $status = $request->status ?? 'succeeded';
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
        $transactions->paginate();
        $result = $transactions->get()
            ->each(function (Transaction $transaction) {
                $donation = $transaction->donation();
                $user = $donation->user();
                return [
                    'id' => $transaction->id,
                    'status' => $donation->status,
                    'date_time' => $transaction->transaction_date,
                    'amount' => $transaction->amount,
                    'type' => $donation->type,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'stripe_fee' => $transaction->stripe_fee,
                    'city' => $user->city,
                    'state' => $user->state
                ];
            });
        return response($result->paginate(), 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Transaction  $transaction
     * @return \Illuminate\Http\Response
     */
    public function show(Transaction $transaction)
    {
        return response(['transaction' => new ReponseResource($transaction), 'message' => 'Retrieved successfully'], 200);
    }
}
