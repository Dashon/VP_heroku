<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDonationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('description');
            $table->unsignedInteger('amount');
            $table->enum('status', array('active','paused','inactive','payment_failed'))->default(('active'));
            $table->enum('type', array('round_up', 'once', 'monthly'));
            $table->date('start_date');
            $table->string('stripe_token');
            $table->timestamps();
            $table->unsignedInteger('round_up_balance')->nullable();
            $table->date('last_round_up_charge_date')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropForeign('user_id');
        });

        Schema::dropIfExists('donations');
    }
}
