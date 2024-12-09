<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 40);
            $table->unsignedInteger('product_type');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('trade_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trade_type')->unique();
            $table->string('name', 10);
            $table->string('caption', 30);
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('tradings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('check_no')->nullable();
            $table->unsignedInteger('member_code');
            $table->date('date');
            $table->unsignedInteger('trade_type');
            $table->integer('amount');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('trade_details', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('trade_id');
            $table->unsignedInteger('product_id');
            $table->integer('amount');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });

        Schema::create('depo_realtime', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('member_code');
            $table->unsignedInteger('product_id');
            $table->integer('amount');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('trade_types');
        Schema::dropIfExists('tradings');
        Schema::dropIfExists('trade_details');
        Schema::dropIfExists('depo_realtime');
    }
};
