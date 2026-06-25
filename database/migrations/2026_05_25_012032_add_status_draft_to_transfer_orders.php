<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_cancelled')->comment('draft/active/cancelled');
            $table->json('draft_data')->nullable()->after('status')->comment('草稿数据');
        });
        // 更新现有数据
        DB::table('transfer_orders')->where('is_cancelled', 1)->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn(['status', 'draft_data']);
        });
    }
};
