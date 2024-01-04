<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('admin_logs', function (Blueprint $table) {
            $table->ulid('id', 36)->primary();
            $table->ulid('access_token_id', 36)->index('log_access_token_id');
            $table->text('url');
            $table->text('method');
            $table->text('ip');
            $table->string('user_agent')->nullable();
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_logs');
    }
}
