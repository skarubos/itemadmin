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
        // Add foreign key constraints
        Schema::table('tradings', function (Blueprint $table) {
            $table->foreign('member_id')->references('member_code')->on('users');
        });

        Schema::table('orders_details', function (Blueprint $table) {
            $table->foreign('trading_id')->references('id')->on('tradings');
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('dopo_details', function (Blueprint $table) {
            $table->foreign('trading_id')->references('id')->on('tradings');
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('depo_realtime', function (Blueprint $table) {
            $table->foreign('member_id')->references('member_code')->on('users');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('depo_realtime', function (Blueprint $table) {
            $table->dropForeign(['member_id', 'product_id']);
        });

        Schema::table('dopo_details', function (Blueprint $table) {
            $table->dropForeign(['trading_id', 'product_id']);
        });

        Schema::table('orders_details', function (Blueprint $table) {
            $table->dropForeign(['trading_id', 'product_id']);
        });

        Schema::table('tradings', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });
    }
};
    
