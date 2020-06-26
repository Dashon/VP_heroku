<?php

namespace App\Http\Controllers\Api;

use App\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use AlexVargash\LaravelStripePlaid\StripePlaid;
use App\Donation;
use App\Notifications\Donation\TransactionFailed;
use App\Notifications\Donation\TransactionSuccessful;
use App\Notifications\PaymentMethod\NewPaymentMethod;
use App\Transaction;
use Carbon\Carbon;
use Stripe\Stripe;

class WebHooksController extends Controller
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
    public function stripeWebHook(Request $request)

    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $request = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

        $event = null;
        if ($endpoint_secret) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $request,
                    $sig_header,
                    $endpoint_secret
                );
            } catch (\Exception $e) {
                return response(['error' => $e->getMessage()], 403);
            }
        } else {

            $event = $request;
        }
        $type = $event['type'];
        $object = $event['data']['object'];

        if ($type == 'payment_intent.succeeded') {
            //  $logger->info('🔔 A SetupIntent has successfully set up a PaymentMethod for future use.');
            if ($object->metadata->donation_id) {
                $donation = Donation::findOrFail($object->metadata->donation_id);
                if ($donation->status == 'payment_failed') {
                    $donation->update(['status' => 'active']);
                }

                $stripeFee = 0;
                if ($object->charges['data']) {
                    $charge = \Stripe\Charge::retrieve(
                        [
                            "id" => $object->charges['data'][0],
                            "expand" => ["balance_transaction"],
                        ]
                    );
                }

                if ($charge->balance_transaction) {
                    $key = array_search('stripe_fee', array_column($charge->balance_transaction->fee_details, 'type'));
                    $stripeFee = $charge->balance_transaction->fee_details[$key]->amount;
                }

                $transaction = Transaction::where('stripe_payment_intent', $object->id)->first();
                if ($transaction) {
                    $transaction->update([
                        'status' => $object->status,
                        'stripeFee' => $stripeFee,
                        'transaction_date' =>  Carbon::today(),
                    ]);
                } else {
                    $transaction = new Transaction([
                        'transaction_type' => 'CHARGE',
                        'status' => $object->status,
                        'stripe_payment_intent' => $object->id,
                        'transaction_date' =>  Carbon::today(),
                        'amount' => $this->donation->amount,
                        'stripeFee' => $stripeFee
                    ]);
                    $donation->transactions()->save($transaction);
                }
                $donation->last_charge_date = Carbon::today();
                $donation->user()->notify(new TransactionSuccessful());
                return response(['result' => "updated Transaction"], 200);
            }
        }

        if ($type == 'payment_method.attached') {

            if ($object && $object->billing_details->email) {
                $user = User::where('email', $object->billing_details->email)->firstOrFail();
                $user->notify(new NewPaymentMethod());
            }
        }

        if ($type == 'payment_intent.payment_failed') {
            if ($object->metadata->donation_id) {
                $donation = Donation::findOrFail('id', $object->metadata->donation_id);
                if ($donation->status == 'active') {
                    $donation->update(['status' => 'payment_failed']);
                }

                $transaction = Transaction::where('stripe_payment_intent', $object->id)->firstOrFail();
                $transaction->update(['status' => $object->status]);


                $donation->user()->notify(new TransactionFailed());
                return response(['result' => "updated Transaction"], 200);
            }
        }


        return response(['status' => 'success'], 200);
    }
}
