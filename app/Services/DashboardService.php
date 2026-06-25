<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetIntake;
use App\Models\AssetDisposal;
use App\Models\User;

class DashboardService
{
    public function getData(): array
    {
        $totalAssets = Asset::count();
        $deptCount = Asset::select('department')->distinct()->whereNotNull('department')->count();

        // 用字典编码查询（数据库存的是编码不是中文）
        $statusCodes = \App\Models\DepartmentCode::type('status')->pluck('code');
        $statusCounts = [];
        foreach ($statusCodes as $code) {
            $statusCounts[$code] = Asset::where('status', $code)->count();
        }

        $catCounts = Asset::selectRaw('category, count(*) as cnt')
            ->groupBy('category')->orderByDesc('cnt')->get();

        $depts = Asset::selectRaw('department, count(*) as cnt')
            ->whereNotNull('department')
            ->groupBy('department')->orderByDesc('cnt')->get();

        // 本月入库/报废统计
        $monthStart = now()->startOfMonth();
        $intakeThisMonth = AssetIntake::where('status', 'active')
            ->where('intake_date', '>=', $monthStart)
            ->count();
        $disposalThisMonth = AssetDisposal::where('status', 'active')
            ->where('disposal_date', '>=', $monthStart)
            ->count();

        return compact('totalAssets', 'deptCount', 'statusCounts', 'catCounts', 'depts', 'intakeThisMonth', 'disposalThisMonth');
    }
}
