<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->nullable();
            $table->string('department', 100)->nullable()->index();
            $table->string('room', 50)->nullable();
            $table->string('ip', 45)->unique();
            $table->string('mac', 17)->index();
            $table->string('sn', 200)->nullable()->index();
            $table->string('category', 50)->default('计算机');
            $table->string('status', 20)->default('在用');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
