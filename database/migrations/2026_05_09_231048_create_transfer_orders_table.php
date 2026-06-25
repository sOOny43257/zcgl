<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 50)->unique();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->json('log_ids');
            $table->string('from_dept', 100)->nullable();
            $table->string('to_dept', 100)->nullable();
            $table->string('from_user', 100)->nullable();
            $table->string('to_user', 100)->nullable();
            $table->string('operator', 100)->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
