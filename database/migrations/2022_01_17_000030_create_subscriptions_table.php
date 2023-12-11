<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->ulid('id',36)->primary();
            $table->ulid('user_id',36)->index('subscription_user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->ulid('plan_id',36)->index('sub_plan_id');
            $table->foreign('plan_id')->references('id')->on('plans');
            $table->dateTime('activated_at');
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('cancels_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('expired_at')->nullable();
            $table->integer('status');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
