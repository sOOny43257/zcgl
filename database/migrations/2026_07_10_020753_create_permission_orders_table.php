<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->nullable()->index();
            $table->string('status', 16)->default('draft')->index();

            $table->string('source_doc_path')->nullable();
            $table->string('source_file_name')->nullable();

            $table->string('department')->nullable()->index();
            $table->string('fill_date')->nullable();

            $table->json('items')->nullable();

            $table->string('voided_by')->nullable();
            $table->dateTime('voided_at')->nullable();
            $table->boolean('paper_submitted')->default(false);
            $table->dateTime('paper_submitted_at')->nullable();

            $table->json('draft_data')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_orders');
    }
};
