<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_codes', function (Blueprint $table) {
            // Drop single-column unique on 'code'
            $table->dropUnique('department_codes_code_unique');
            // Add composite unique on (type, code)
            $table->unique(['type', 'code'], 'idx_type_code');
        });
    }

    public function down(): void
    {
        Schema::table('department_codes', function (Blueprint $table) {
            $table->dropUnique('idx_type_code');
            $table->unique('code');
        });
    }
};
