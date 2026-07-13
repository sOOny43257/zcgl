<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_logs', function (Blueprint $table) {
            // 关联调拨单号（如 DB20260713001），用于变更历史展示来源单据
            $table->string('reference_no', 30)->nullable()->comment('关联调拨单号，标识该变更来自哪张调拨单/导入批次')->after('new_value');
            $table->index('reference_no');
        });
    }

    public function down(): void
    {
        Schema::table('asset_logs', function (Blueprint $table) {
            $table->dropIndex(['reference_no']);
            $table->dropColumn('reference_no');
        });
    }
};
