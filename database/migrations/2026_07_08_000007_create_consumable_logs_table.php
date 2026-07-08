<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumable_id')->nullable();
            $table->string('consumable_name', 200)->comment('耗材名称冗余');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 100)->nullable();
            $table->string('action', 30)->index()->comment('操作类型');
            $table->text('description')->nullable()->comment('可读描述');
            $table->integer('old_stock')->nullable()->comment('变更前库存');
            $table->integer('new_stock')->nullable()->comment('变更后库存');
            $table->string('reference_type', 50)->nullable()->comment('关联单据类型');
            $table->unsignedBigInteger('reference_id')->nullable()->comment('关联单据ID');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_logs');
    }
};
