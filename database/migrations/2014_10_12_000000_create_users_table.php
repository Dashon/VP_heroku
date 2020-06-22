<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('terms')->nullable();
            $table->timestamp('policy')->nullable();
            $table->timestamp('privacy')->nullable();
            $table->string('plaid_id')->nullable();
            $table->enum('type', array('admin', 'moderator', 'donator'))->default('donator');
            $table->string('password');
            $table->boolean('active')->default(1);
            $table->boolean('is_beneficiary')->default(0);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
