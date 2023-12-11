<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::enableForeignKeyConstraints();
        Schema::create('plans', function (Blueprint $table) {
            $table->ulid('id',36)->primary();
            $table->string('tag')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('data')->default('[]');
            $table->decimal('price')->default('0.00');
            $table->integer('custom');
            $table->integer('tier');
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('plans');
    }
}
