<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('部门编号');
            $table->string('name', 100)->comment('部门名称');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_codes');
    }
};
