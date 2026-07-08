<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumable_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('inventory_no', 50)->unique()->comment('盘点单号 HC-PD-YYYYMMDD-NNN');
            $table->date('inventory_date')->comment('盘点日期');
            $table->unsignedBigInteger('operator_id')->nullable()->comment('盘点人ID');
            $table->string('operator_name', 100)->comment('盘点人姓名');
            $table->string('status', 20)->default('draft')->index()->comment('draft/completed');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_inventories');
    }
};
