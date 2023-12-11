<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('personal_tokens', function (Blueprint $table) {
            $table->ulid('id',36)->primary();
            $table->ulid('user_id',36)->index('personal_token_user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('key', 32);
            $table->string('secret', 64);
            $table->string('secret_salt', 16);
            $table->integer('rate_limit_mode');
            $table->integer('ip_rule');
            $table->json('ip_range')->default('[]');
            $table->integer('country_rule');
            $table->json('country_range')->default('[]');
            $table->json('permissions')->default('[]');
            $table->string('hmac_token', 255);
            $table->boolean('hidden')->default(false);
            $table->boolean('disable_logging')->default(false);
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('expires_at')->nullable();
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
        Schema::dropIfExists('personal_tokens');
    }
}
