<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropUnique('transfer_orders_order_no_unique');
            $table->string('order_no', 50)->nullable()->change();
            $table->unique('order_no');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropUnique('transfer_orders_order_no_unique');
            $table->string('order_no', 50)->nullable(false)->change();
            $table->unique('order_no');
        });
    }
};
