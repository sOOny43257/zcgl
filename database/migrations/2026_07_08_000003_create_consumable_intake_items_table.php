<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_intake_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intake_order_id');
            $table->unsignedBigInteger('consumable_id');
            $table->integer('quantity')->comment('入库数量');
            $table->decimal('unit_price', 10, 2)->nullable()->comment('本次入库单价');
            $table->decimal('subtotal', 12, 2)->nullable()->comment('小计');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('intake_order_id')->references('id')->on('consumable_intake_orders')->cascadeOnDelete();
            $table->foreign('consumable_id')->references('id')->on('consumables');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_intake_items');
    }
};
