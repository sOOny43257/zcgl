<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_intake_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique()->comment('入库单号 HC-RK-YYYYMMDD-NNN');
            $table->date('intake_date')->comment('入库日期');
            $table->string('supplier_code', 50)->nullable()->comment('供应商编码');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('经办人ID');
            $table->string('operator_name', 100)->comment('经办人姓名');
            $table->string('status', 20)->default('draft')->index()->comment('draft/completed/cancelled');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_intake_orders');
    }
};
