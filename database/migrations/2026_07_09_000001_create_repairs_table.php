<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repairs', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->nullable()->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('repair_date');
            $table->string('fault_category', 20)->nullable();       // 硬件/软件/网络/其他
            $table->text('fault_description')->nullable();
            $table->string('repair_method', 20)->nullable();        // 内部/外包
            $table->string('vendor', 200)->nullable();              // 维修单位/人员
            $table->decimal('cost', 10, 2)->nullable();
            $table->date('expected_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->json('attachments')->nullable();
            $table->string('operator', 100)->nullable();            // 经办人
            $table->string('status', 20)->default('draft');         // draft/submitted/in_progress/completed/cancelled
            $table->string('previous_asset_status', 20)->nullable();// 送修前资产状态
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repairs');
    }
};
