<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->change();
            $table->string('mac', 17)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('ip', 45)->nullable(false)->change();
            $table->string('mac', 17)->nullable(false)->change();
        });
    }
};
