<?php

namespace App\Http\Controllers\Api;

use App\BankAccount;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReponseResource;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use AlexVargash\LaravelStripePlaid\StripePlaid;
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
        Stripe::setApiKey(getenv('STRIPE_SECRET'));
        $event = $request;
        // Parse the message body (and check the signature if possible)
        $webhookSecret = getenv('STRIPE_WEBHOOK_SECRET');
        if ($webhookSecret) {
          try {
              $event = \Stripe\Webhook::constructEvent(
                $request,
                $request->header('stripe-signature'),
                $webhookSecret
            );
          } catch (\Exception $e) {
            return response([ 'error' => $e->getMessage() ],403);
          }
        } else {
          $event = $request;
        }
        $type = $event['type'];
        $object = $event['data']['object'];

        if ($type == 'payment_intent.succeeded') {
        //  $logger->info('🔔 A SetupIntent has successfully set up a PaymentMethod for future use.');
        print_r($object);
        return response(['result'=>$object->metadata],200);
         }

        if ($type == 'payment_method.attached') {
        //  $logger->info('🔔 A PaymentMethod has successfully been saved to a Customer.');

          // At this point, associate the ID of the Customer object with your
          // own internal representation of a customer, if you have one.
        }

        if ($type == 'payment_intent.payment_failed') {

        }

        return response([ 'status' => 'success' ],200);
    }

}
