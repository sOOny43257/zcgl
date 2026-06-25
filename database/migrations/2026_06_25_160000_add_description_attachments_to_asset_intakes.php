<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_intakes', function (Blueprint $table) {
            $table->text('description')->nullable()->after('remarks')->comment('入库说明');
            $table->json('attachments')->nullable()->after('description')->comment('附件路径列表');
        });
    }

    public function down(): void
    {
        Schema::table('asset_intakes', function (Blueprint $table) {
            $table->dropColumn(['description', 'attachments']);
        });
    }
};
