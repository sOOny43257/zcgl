<?php

use App\Models\Asset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('asset_code', 20)->nullable()->after('id')->unique();
            $table->string('financial_code', 50)->nullable()->after('asset_code');
        });

        // 为已有资产生成自有编号
        $year = date('y');
        $counters = [];

        Asset::whereNull('asset_code')->orderBy('id')->each(function ($asset) use ($year, &$counters) {
            $prefix = match (true) {
                str_contains($asset->category, '计算机') => 'C',
                $asset->category === '打印机' => 'P',
                default => 'D',
            };

            if (!isset($counters[$prefix])) {
                // 查找该前缀最大编号
                $last = Asset::where('asset_code', 'like', $prefix . $year . '%')
                    ->orderByDesc('asset_code')->first();
                if ($last) {
                    $counters[$prefix] = (int) substr($last->asset_code, 3) + 1;
                } else {
                    $counters[$prefix] = 1;
                }
            }

            $code = $prefix . $year . str_pad($counters[$prefix], 3, '0', STR_PAD_LEFT);
            $counters[$prefix]++;

            $asset->updateQuietly(['asset_code' => $code]);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['asset_code', 'financial_code']);
        });
    }
};
