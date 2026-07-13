<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('import')->comment('操作类型: import=新增, update=更新, mixed=混合');
            $table->string('file_name')->nullable()->comment('上传文件名');
            $table->unsignedInteger('total_rows')->default(0)->comment('CSV总行数');
            $table->unsignedInteger('inserted')->default(0)->comment('新增条数');
            $table->unsignedInteger('updated')->default(0)->comment('更新条数');
            $table->unsignedInteger('skipped')->default(0)->comment('跳过条数');
            $table->json('changed_details')->nullable()->comment('变更详情（只含实际有变化的条目）');
            $table->json('errors')->nullable()->comment('错误明细');
            $table->unsignedBigInteger('transfer_order_id')->nullable()->comment('关联的调拨单ID（仅当非财务编码字段有变化时）');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name')->nullable()->comment('操作人姓名');
            $table->timestamps();

            $table->index('operator_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
