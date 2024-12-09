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
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('latest_trade')->references('id')->on('tradings');
        });

        Schema::table('tradings', function (Blueprint $table) {
            $table->foreign('member_code')->references('member_code')->on('users');
            $table->foreign('trade_type')->references('trade_type')->on('trade_types');
        });

        Schema::table('trade_details', function (Blueprint $table) {
            $table->foreign('trade_id')->references('id')->on('tradings');
            $table->foreign('product_id')->references('id')->on('products');
        });

        Schema::table('depo_realtime', function (Blueprint $table) {
            $table->foreign('member_code')->references('member_code')->on('users');
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['latest_trade']);
        });

        Schema::table('tradings', function (Blueprint $table) {
            $table->dropForeign(['member_code']);
            $table->dropForeign(['trade_type']);
        });

        Schema::table('trade_details', function (Blueprint $table) {
            $table->dropForeign(['trade_id']);
            $table->dropForeign(['product_id']);
        });

        Schema::table('depo_realtime', function (Blueprint $table) {
            $table->dropForeign(['member_code']);
            $table->dropForeign(['product_id']);
        });
    }
};
