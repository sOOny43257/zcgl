<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('purchase_date')->nullable()->after('remarks')->comment('采购日期');
            $table->decimal('purchase_price', 10, 2)->nullable()->after('purchase_date')->comment('采购单价');
            $table->string('supplier', 200)->nullable()->after('purchase_price')->comment('供应商');
            $table->date('warranty_date')->nullable()->after('supplier')->comment('保修到期');
            $table->unsignedBigInteger('intake_id')->nullable()->after('warranty_date')->comment('关联入库单ID');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['purchase_date', 'purchase_price', 'supplier', 'warranty_date', 'intake_id']);
        });
    }
};
