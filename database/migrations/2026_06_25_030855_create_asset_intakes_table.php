<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_intakes', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique()->comment('入库单号');
            $table->date('intake_date')->comment('入库日期');
            $table->string('supplier', 200)->nullable()->comment('供应商');
            $table->string('purchase_order_no', 100)->nullable()->comment('采购单号/合同号');
            $table->decimal('total_amount', 12, 2)->nullable()->comment('总金额');
            $table->string('operator', 100)->comment('经办人');
            $table->string('approver', 100)->nullable()->comment('验收人');
            $table->string('status', 20)->default('draft')->comment('draft/active/cancelled');
            $table->json('draft_data')->nullable()->comment('草稿数据(资产明细)');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_intakes');
    }
};
