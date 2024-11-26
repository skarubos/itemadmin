<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 40);
            $table->integer('product_type');
            $table->timestamps();
        });

        Schema::create('tradings', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id');
            $table->date('date');
            $table->integer('trading_type');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('orders_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('dopo_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trading_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('depo_realtime', function (Blueprint $table) {
            $table->id();
            $table->integer('member_id');
            $table->integer('status');
            $table->unsignedBigInteger('product_id');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depo_realtime');
        Schema::dropIfExists('dopo_details');
        Schema::dropIfExists('orders_details');
        Schema::dropIfExists('tradings');
        Schema::dropIfExists('products');
    }
};

