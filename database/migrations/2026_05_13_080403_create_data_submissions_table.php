<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('room', 50)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('mac', 17)->nullable();
            $table->string('sn', 200)->nullable()->index();
            $table->string('status', 20)->default('pending');
            $table->json('errors')->nullable();
            $table->json('suggestions')->nullable();
            $table->text('submit_log')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_submissions');
    }
};
