<?php

namespace App\Console\Commands;

use App\BankAccount;
use App\BankAccountTransaction;
use App\Donation;
use Stripe\Customer;
use Stripe\Stripe;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use AlexVargash\LaravelStripePlaid\StripePlaid;
use App\Transaction;
use App\Services\Cashier;

class RoundUpDonations extends Command
{

    protected $name = "round_up_donations";
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donation:round_up';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for round-up donations';

    /**
     * Execute the console command.
     *
     * @return mixed|void
     */
    public function handle()
    {
        $donations = Donation::where('type', 'round_up')->where('status', 'active')->get();
        $todayDate = date('YYYY-MM-DD');

        foreach ($donations as $donation) {
            $bankAccount = BankAccount::where('stripe_token', $donation->stripe_payment_token)->firstOrFail();

            $start_date = Carbon::createFromFormat('YYYY-MM-DD',  $donation->start_date);
            if ($donation->last_charge_date) {
                $start_date = Carbon::createFromFormat('YYYY-MM-DD', $donation->last_charge_date);
            }

            $last_transaction = BankAccountTransaction::orderBy('transaction_date', 'DESC')->where('bank_account_id', $bankAccount->id)->first();
            if ($last_transaction) {
                $start_date =  Carbon::createFromFormat('YYYY-MM-DD', $last_transaction->transaction_date);
            }

            $stripePlaid = new StripePlaid();
            $access_token = $stripePlaid->exchangePublicToken(env('PLAID_PUBLIC_KEY'));

            $response = Http::retry(3, 100)->withHeaders([
                'Content-Type' => 'application/json'
            ])->post('https://sandbox.plaid.com/transactions/get', [
                "client_id" => env('PLAID_CLIENT_ID'),
                "secret" => env('PLAID_SECRET'),
                "access_token" => $access_token,
                "account_ids" => [$bankAccount->plaid_id],
                "start_date" => $start_date,
                "end_date" => $todayDate,
            ]);

            if ($response->successful()) {
                foreach ($response->transactions as $transaction) {
                    $roundUpAmount = round($transaction->amount) - $transaction->amount;
                    if ($roundUpAmount == 0) {
                        continue;
                    }
                    $bankAccountTransaction = new BankAccountTransaction([
                        'merchant' => $transaction->date,
                        'amount' => $transaction->amount,
                        'round_up_amount' => $roundUpAmount,
                        'amount' => $transaction->amount,
                        'transaction_date' => $transaction->date,
                    ]);
                    $bankAccount->bankAccountTransactions()->save($bankAccountTransaction);

                    $donation->update(['round_up_balance' => $donation->round_up_balance + $transaction->amount]);
                    if ($donation->round_up_balance >= $donation->amount) {
                        //charge account and break;
                        $cashier = new Cashier();
                        $cashier->checkout($donation);
                        $donation->update(['round_up_balance' => 0, 'last_charge_date', $todayDate]);
                        break;
                    }
                }
            }
        }
    }
}
