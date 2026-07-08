<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('consumable_id');
            $table->string('department_code', 50)->index()->comment('使用部门编码');
            $table->integer('quantity')->comment('领用数量');
            $table->date('usage_date')->comment('领用日期');
            $table->string('reason', 500)->comment('领用事由');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('操作人ID');
            $table->string('operator_name', 100)->comment('操作人姓名');
            $table->timestamps();

            $table->foreign('consumable_id')->references('id')->on('consumables');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');
    }
};
