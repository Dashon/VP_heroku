<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameStripeTxToStripePI extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bank_account_transactions', function (Blueprint $table) {
            $table->renameColumn('stripe_tx', 'stripe_payment_intent');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('stripe_tx', 'stripe_payment_intent');
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
            $table->renameColumn('stripe_payment_intent', 'stripe_tx');
        });
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('stripe_payment_intent', 'stripe_tx');
        });
    }
}
