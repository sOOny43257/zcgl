<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            // 手动填写的操作人（可与登录用户不同，记录实际执行人）
            $table->string('operator', 100)->nullable()->comment('手动填写的操作人姓名')->after('operator_name');
            // 导入原因
            $table->text('import_reason')->nullable()->comment('本次导入/更新的原因说明')->after('operator');
            // 作废标记
            $table->boolean('is_cancelled')->default(false)->comment('是否已作废')->after('import_reason');
            $table->timestamp('cancelled_at')->nullable()->comment('作废时间')->after('is_cancelled');

            $table->index('is_cancelled');
        });
    }

    public function down(): void
    {
        Schema::table('import_logs', function (Blueprint $table) {
            $table->dropIndex(['is_cancelled']);
            $table->dropColumn(['operator', 'import_reason', 'is_cancelled', 'cancelled_at']);
        });
    }
};
