<?php

namespace App\Http\Controllers\Api\Admin;

use App\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paginated = User::paginate();
        $paginated->getCollection()->each->withContributionData();
        return response($paginated, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user->withContributionData();
        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

    public function userDonations(User $user)
    {
        $paginated = $user->donations()->with('transactions')->paginate();
        $paginated->getCollection()->each->withSummary();

        return response($paginated, 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $current_user = auth()->user();
        $this->validate($request, [
            'type' => ['string', 'in:', ['admin', 'moderator', 'donator']],
            'phone' => 'phone:AUTO,US',
            'email' => 'email|required|unique:users'
        ]);

        if (request()->has('active') || request()->has('type')) {
            if ($this->type == 'admin' &&  $current_user->type != 'admin') {
                return response(['message' => 'only an admin can activate/deactivate an admin account'], 405);
            }
        }
        $user->update([
            'status' => $request->status,
            'type' => $request->type ?? $user->type,
            'phone' => $request->phone ?? $user->phone,
            'email' => $request->email ?? $user->email
        ]);

        $user->withContributionData();
        return response(['user' => new ReponseResource($user), 'message' => 'Retrieved successfully'], 200);
    }

    /**
     * View all user Payment sources
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function paymentSources(Request $request, User $user)
    {
        $current_user = auth()->user();
        if (($current_user->type != 'admin' || $current_user->type != 'moderator') && $user->id != $current_user->id) {
            response("Not Authorized", 401);
        }
        $payment_sources = array();
        if ($user->hasPaymentMethod()) {
            foreach ($user->paymentMethods() as $card) {
                array_push($payment_sources, [
                    'id' => $card->id,
                    'type' => ucwords($card->card->brand),
                    'last_four' => $card->card->last4,
                ]);
            }

            foreach ($user->bankAccounts() as $bankAccount) {
                array_push($payment_sources, [
                    'id' => $bankAccount->id,
                    'type' => "Bank Account",
                    'last_four' => $bankAccount->last4,
                ]);
            }
        }

        return response(['paymentSources' => $payment_sources, 'message' => 'Retrieved successfully'], 200);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param \App\User $user
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(User $user)
    {
        $current_user = auth()->user();
        if ($current_user->type != 'admin' || $current_user->type != 'moderator') {
            return response(['message' => 'only an admin can edit accounts'], 405);
        }
        if ($this->type == 'admin' &&  $current_user->type != 'admin') {
            return response(['message' => 'only an admin can edit an admin account'], 405);
        }
        $this->active = 0;
        $this->save();

        return response(['message' => 'DeActivated']);
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

    public function store(User $user)
    {
        return response(['message' => 'Not Implemented'], 501);
    }
}
