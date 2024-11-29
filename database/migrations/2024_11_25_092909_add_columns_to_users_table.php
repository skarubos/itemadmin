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
        Schema::table('users', function (Blueprint $table) {
            $table->string('name_kana', 40)->nullable()->after('name');
            $table->unsignedInteger('member_code')->unique()->after('name_kana');
            $table->string('phone_number', 11)->after('member_code');
            $table->unsignedInteger('sales')->default(0)->after('phone_number');
            $table->unsignedInteger('depo_status')->default(0)->after('sales');
            $table->unsignedInteger('sub_leader')->default(0)->after('depo_status');
            $table->unsignedInteger('sub_number')->default(0)->after('sub_leader');
            $table->unsignedInteger('sub_now')->default(0)->after('sub_number');
            $table->unsignedInteger('priority')->default(4)->after('sub_now');
            $table->unsignedInteger('permission')->default(2)->after('priority');
            $table->unsignedInteger('status')->default(1)->after('permission');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->change();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->change();

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_kana');
            $table->dropUnique(['member_code']);
            $table->dropColumn('member_code');
            $table->dropColumn('phone_number');
            $table->dropColumn('sales');
            $table->dropColumn('depo_status');
            $table->dropColumn('sub_leader');
            $table->dropColumn('sub_number');
            $table->dropColumn('sub_now');
            $table->dropColumn('priority');
            $table->dropColumn('permission');
            $table->dropColumn('status');
            $table->timestamp('updated_at')->change();
            $table->timestamp('created_at')->change();
            
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
