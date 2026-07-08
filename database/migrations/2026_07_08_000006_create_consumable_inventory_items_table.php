<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('consumable_id');
            $table->integer('book_quantity')->comment('账面库存快照');
            $table->integer('actual_quantity')->comment('实际盘点数量');
            $table->integer('difference')->comment('差异 (actual - book)');
            $table->string('reason', 500)->nullable()->comment('差异原因');
            $table->boolean('adjusted')->default(false)->comment('是否已调整库存');
            $table->timestamps();

            $table->foreign('inventory_id')->references('id')->on('consumable_inventories')->cascadeOnDelete();
            $table->foreign('consumable_id')->references('id')->on('consumables');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_inventory_items');
    }
};
