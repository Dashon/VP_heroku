<?php

namespace App\Http\Controllers\Api;

use App\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use AlexVargash\LaravelStripePlaid\StripePlaid;
use App\Notifications\PaymentMethod\DeletedPaymentMethod;
use App\Notifications\PaymentMethod\NewPaymentMethod;

class PaymentSourcesController extends Controller
{

    /**
     * View all user Payment sources
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $current_user = auth()->user();
        $payment_sources = $current_user->paymentSources();
        return response(['paymentSources' => $payment_sources, 'message' => 'Retrieved successfully'], 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeBankAccount(Request $request)
    {
        $current_user = auth()->user();
        $data = $request->all();

        $validator = Validator::make($data, [
            'routing_number' => 'required|max:255',
            'account_number' => 'required|max:255',
            'plaid_id' => 'required|max:255',
            'plaid_public_token' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        if ($current_user->bankAccounts()->where('account_number', $request->account_number)->where('routing_number', $request->routing_number)->count()) {
            return response("Account Already Exist", 409);
        }

        $accountId = $request->plaid_id;
        $publicToken = $request->plaid_public_token;

        $stripePlaid = new StripePlaid();
        $stripeToken = $stripePlaid->getStripeToken($publicToken, $accountId);

        $bankAccount = BankAccount::create($data);
        $bankAccount->stripe_id = $stripeToken;

        $current_user->bankAccounts()->save($bankAccount);

        return response(['bankAccount' => new ReponseResource($bankAccount), 'message' => 'Created successfully'], 200);
    }


    public function createSetupIntent()
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        // The PaymentMethod will be stored to this Customer for later use.
        $current_user = auth()->user();

        $setup_intent = $stripe->paymentIntents->create([
            'customer' => $current_user->stripe_id
        ]);
        // Send Setup Intent details to client
        return response(['client_secret' => new ReponseResource($setup_intent->client_secret), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  string $stripe_id
     * @return \Illuminate\Http\Response
     */
    public function showPaymentSource(string $stripe_id)
    {
        $payment_source = $this->getPaymentSource($stripe_id, auth()->user());
        if ($payment_source) {
            return response(['paymentSource' => $payment_source, 'message' => 'Retrieved successfully'], 200);
        }
        return response(['paymentSources' => null, 'message' => 'Payment Source Not Found'], 404);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\BankAccount $bankAccount
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroyPaymentSource(string $stripe_id)
    {
        $current_user = auth()->user();

        $bankAccount = $current_user->bankAccounts()->where('stripe_id', $stripe_id)->first();
        if ($bankAccount) {
            $bankAccount->delete();
            $current_user->notify(new DeletedPaymentMethod());
            return response(['message' => 'Deleted']);
        }

        $stripeCard = $current_user->findPaymentMethod($stripe_id);
        if ($stripeCard) {
            $stripeCard->delete();
            $current_user->notify(new DeletedPaymentMethod());
            return response(['message' => 'Deleted']);
        }
        return response(['message' => 'Not Found'], 404);
    }

    private function getPaymentSource(string $stripe_id, User $user)
    {
        $bankAccount = $user->bankAccounts()->where('stripe_id', $stripe_id)->first();
        $payment_source = [];
        if ($bankAccount) {
            $payment_source = [
                'id' => $bankAccount->id,
                'type' => "Bank Account",
                'last_four' => $bankAccount->last4,
            ];
        }

        $stripeCard = $user->findPaymentMethod($stripe_id);
        if ($stripeCard) {
            $payment_source = [
                'id' => $stripeCard->id,
                'type' => ucwords($stripeCard->card->brand),
                'last_four' => $stripeCard->card->last4,
            ];
        }

        return  $payment_source;
    }
}
