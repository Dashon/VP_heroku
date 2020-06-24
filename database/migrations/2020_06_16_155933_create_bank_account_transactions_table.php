<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankAccountTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bank_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('bank_account_id');
            $table->string('stripe_tx');
            $table->string('merchant');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('round_up_amount');
            $table->date('transaction_date');

            $table->foreign('bank_account_id')->references('id')->on('bank_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->dropForeign('bank_account_id');
        });

        Schema::dropIfExists('bank_account_transactions');
    }
}
