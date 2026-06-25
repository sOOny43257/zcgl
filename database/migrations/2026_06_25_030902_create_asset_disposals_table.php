<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique()->comment('报废单号');
            $table->date('disposal_date')->comment('报废日期');
            $table->string('disposal_method', 50)->default('报废处置')->comment('报废处置/捐赠/回收');
            $table->string('reason', 500)->nullable()->comment('报废原因');
            $table->string('operator', 100)->comment('经办人');
            $table->string('approver', 100)->nullable()->comment('审批人');
            $table->string('status', 20)->default('draft')->comment('draft/active/cancelled');
            $table->json('draft_data')->nullable()->comment('草稿数据(资产ID+快照)');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
