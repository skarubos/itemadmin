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
            $table->string('name_kana', 40)->nullable();
            $table->integer('member_code')->unique();
            $table->string('phone_number', 11);
            $table->integer('permission')->default(2);
            $table->integer('depo_status')->default(2);

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_kana');
            $table->dropColumn('member_code');
            $table->dropColumn('phone_number');
            $table->dropColumn('permission');
            $table->dropColumn('depo_status');

            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
