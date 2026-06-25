<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_borrows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 50)->unique()->comment('借用单据号');
            $table->string('borrower', 100)->comment('借用人');
            $table->string('department', 100)->nullable()->comment('借用部门');
            $table->date('borrow_date')->comment('借用日期');
            $table->date('expected_return_date')->nullable()->comment('预计归还日期');
            $table->date('return_date')->nullable()->comment('实际归还日期');
            $table->string('previous_status', 20)->comment('借用前状态');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_borrows');
    }
};
