<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveCardInfoFromUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['card_brand']);
            $table->dropColumn(['card_last_four']);
            $table->dropColumn(['trial_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('card_brand')->nullable();
            $table->string('card_last_four')->nullable();
            $table->string('trial_ends_at')->nullable();

        });
    }
}
