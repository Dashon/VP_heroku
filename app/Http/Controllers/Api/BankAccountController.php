<?php

namespace App\Http\Controllers\API;

use App\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use AlexVargash\LaravelStripePlaid\StripePlaid;

class BankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * @OA\Get(
     *     path="/bbankAccount",
     *     @OA\Response(response="200", description="Display a listing of BBank Accounts.")
     * )
     */
    public function index()
    {
        $current_user = auth()->user();
        $bankAccounts = $current_user->bankAccounts();

        return response(['bankAccounts' => ReponseResource::collection($bankAccounts), 'message' => 'Retrieved successfully'], 200);
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

        $validator = Validator::make($data, [
            'routing_number' => 'required|max:255',
            'account_number' => 'required|max:255',
            'plaid_id' => 'required|max:255',
            'plaid_secrete' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        if($current_user->bankAccounts()->where('account_number', $request->account_number)->where('routing_number', $request->routing_number)){
            return response("Account Already Exist",409);
        }


        $accountId = $request->plaid_id;
        $publicToken = $request->plaid_secrete;

        $stripePlaid = new StripePlaid();
        $stripeToken = $stripePlaid->getStripeToken($publicToken, $accountId);

        $bankAccount = BankAccount::create($data);
        $bankAccount->stripe_id = $stripeToken;

        $current_user->bankAccounts()->save($bankAccount);

        return response(['bankAccount' => new ReponseResource($bankAccount), 'message' => 'Created successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function show(BankAccount $bankAccount)
    {
        return response(['bankAccount' => new ReponseResource($bankAccount), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BankAccount  $bankAccount
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        return response(501);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\BankAccount $bankAccount
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(BankAccount $bankAccount)
    {

        $bankAccount->delete();
        return response(['message' => 'Deleted']);
    }
}
