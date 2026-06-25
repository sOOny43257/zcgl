<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetBorrow;
use App\Models\TransferOrder;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    // 资产列表（支持筛选/搜索/排序）
    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('asset_code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('ip', 'like', "%{$s}%");
            });
        }

        foreach (['departments', 'statuses', 'categories'] as $f) {
            if ($request->filled($f)) {
                $col = rtrim($f, 's');
                $query->whereIn($col, (array) $request->$f);
            }
        }

        $sortField = $request->get('sort', 'id');
        $sortDir = $request->get('direction', 'desc');
        $allowed = ['id', 'asset_code', 'name', 'department', 'status', 'created_at', 'updated_at'];
        if (in_array($sortField, $allowed)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $assets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $assets->items(),
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    // 资产详情（按自有编号或ID查找）
    public function show($code)
    {
        $asset = Asset::where('asset_code', $code)->orWhere('id', $code)->first();

        if (!$asset) {
            return response()->json(['success' => false, 'message' => '资产不存在'], 404);
        }

        $asset->load('logs');
        return response()->json(['success' => true, 'data' => $asset]);
    }

    // 按状态查询
    public function byStatus($status)
    {
        $assets = Asset::where('status', $status)->orderBy('asset_code')->get();
        return response()->json(['success' => true, 'data' => $assets, 'total' => $assets->count()]);
    }

    // 搜索（快速查询）
    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $assets = Asset::where(function ($query) use ($q) {
            $query->where('asset_code', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orWhere('ip', 'like', "%{$q}%");
        })->select('id', 'asset_code', 'name', 'ip', 'department', 'status')
            ->limit(30)->get();

        return response()->json($assets);
    }

    // 借用记录
    public function borrows(Request $request)
    {
        $query = AssetBorrow::with('asset')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->whereNull('return_date');
            } elseif ($request->status === 'returned') {
                $query->whereNotNull('return_date');
            }
        }

        $borrows = $query->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'data' => $borrows->items(),
            'meta' => [
                'current_page' => $borrows->currentPage(),
                'last_page' => $borrows->lastPage(),
                'total' => $borrows->total(),
            ],
        ]);
    }

    // 调拨单列表
    public function transfers(Request $request)
    {
        $query = TransferOrder::with('asset')->orderBy('created_at', 'desc');

        if ($request->filled('cancelled')) {
            $query->where('is_cancelled', $request->cancelled === 'true');
        }

        $transfers = $query->paginate(min((int) $request->get('per_page', 20), 100));

        return response()->json([
            'success' => true,
            'data' => $transfers->items(),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'last_page' => $transfers->lastPage(),
                'total' => $transfers->total(),
            ],
        ]);
    }

    // 统计概览
    public function stats()
    {
        $total = Asset::count();
        $statuses = [];
        foreach (Asset::STATUSES as $s) {
            $statuses[$s] = Asset::where('status', $s)->count();
        }

        $depts = Asset::selectRaw('department, count(*) as count')
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('count')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_assets' => $total,
                'status_distribution' => $statuses,
                'department_distribution' => $depts,
                'borrowing_count' => $statuses['借用'] ?? 0,
            ],
        ]);
    }
}
