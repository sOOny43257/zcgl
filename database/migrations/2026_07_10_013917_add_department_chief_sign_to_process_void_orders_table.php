<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('process_void_orders', function (Blueprint $table) {
            $table->string('department_chief_sign')->nullable()->after('submitter_sign')->comment('科所长签字');
        });
    }

    public function down(): void
    {
        Schema::table('process_void_orders', function (Blueprint $table) {
            $table->dropColumn('department_chief_sign');
        });
    }
};
