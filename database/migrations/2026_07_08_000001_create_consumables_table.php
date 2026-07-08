<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->comment('耗材名称');
            $table->string('category_code', 50)->index()->comment('分类编码');
            $table->string('spec', 200)->nullable()->comment('规格型号');
            $table->string('unit_code', 50)->comment('单位编码');
            $table->string('supplier_code', 50)->nullable()->index()->comment('默认供应商');
            $table->integer('min_stock')->default(0)->comment('安全库存阈值');
            $table->integer('current_stock')->default(0)->comment('当前库存');
            $table->decimal('unit_price', 10, 2)->nullable()->comment('参考单价');
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumables');
    }
};
