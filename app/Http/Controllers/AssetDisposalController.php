<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Models\DepartmentCode;
use Illuminate\Http\Request;

class AssetDisposalController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetDisposal::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('approver', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('date_from')) {
            $query->whereDate('disposal_date', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('disposal_date', '<=', $to);
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $disposals = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        // 为每个报废单附加资产信息
        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');
        $statusMap = DepartmentCode::type('status')->pluck('name', 'code');

        $disposals->getCollection()->transform(function ($d) use ($catMap, $deptMap, $statusMap) {
            $assetIds = $d->draft_data['asset_ids'] ?? [];
            $snapshot = $d->draft_data['snapshot'] ?? [];
            $d->asset_list = [];
            if (!empty($assetIds)) {
                $assets = Asset::whereIn('id', $assetIds)->get();
                foreach ($assets as $a) {
                    $a->category_name = $catMap[$a->category] ?? $a->category;
                    $a->department_name = $deptMap[$a->department] ?? $a->department;
                    $a->status_name = $statusMap[$a->status] ?? $a->status;
                }
                $d->asset_list = $assets;
            } elseif (!empty($snapshot)) {
                $d->asset_list = collect($snapshot)->map(function ($s) use ($catMap, $deptMap) {
                    $s['category_name'] = $catMap[$s['category'] ?? ''] ?? ($s['category'] ?? '');
                    $s['department_name'] = $deptMap[$s['department'] ?? ''] ?? ($s['department'] ?? '');
                    return (object) $s;
                });
            }
            return $d;
        });

        return view('disposals.index', compact('disposals'));
    }

    public function create(Request $request)
    {
        $preselectIds = $request->get('assets', '');
        return view('disposals.create', compact('preselectIds'));
    }

    public function store(Request $request)
    {
        $action = $request->input('_action', 'draft');

        $request->validate([
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|string|max:50',
            'reason' => 'nullable|string|max:500',
            'approver' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $assetIds = $request->input('asset_ids', []);

        // 构建资产快照
        $snapshot = [];
        foreach ($assetIds as $id) {
            $asset = Asset::find($id);
            if ($asset) {
                $snapshot[$id] = [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'name' => $asset->name,
                    'category' => $asset->category,
                    'department' => $asset->department,
                    'room' => $asset->room,
                    'user' => $asset->user,
                    'status' => $asset->status,
                    'sn' => $asset->sn,
                    'brand' => $asset->brand,
                    'model' => $asset->model,
                ];
            }
        }

        $status = 'draft';
        $orderNo = null;

        if ($action === 'submit') {
            $orderNo = AssetDisposal::generateOrderNo();
            $status = 'active';
        }

        $disposal = AssetDisposal::create([
            'order_no' => $orderNo,
            'disposal_date' => $request->disposal_date,
            'disposal_method' => $request->disposal_method,
            'reason' => $request->reason,
            'operator' => auth()->user()->name,
            'approver' => $request->approver,
            'status' => $status,
            'draft_data' => [
                'asset_ids' => $assetIds,
                'snapshot' => $snapshot,
            ],
            'remarks' => $request->remarks,
        ]);

        if ($action === 'submit') {
            $this->applyDisposal($assetIds);
            return redirect()->route('disposals.index')->with('success', "报废单 {$orderNo} 已生效，共报废 " . count($assetIds) . " 项资产");
        }

        return redirect()->route('disposals.edit', $disposal)->with('success', '草稿已保存');
    }

    public function edit(AssetDisposal $disposal)
    {
        if ($disposal->status !== 'draft') {
            return redirect()->route('disposals.index')->with('error', '只能编辑草稿报废单');
        }

        $assetIds = $disposal->draft_data['asset_ids'] ?? [];
        $assets = Asset::whereIn('id', $assetIds)->get();

        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');
        $statusMap = DepartmentCode::type('status')->pluck('name', 'code');

        foreach ($assets as $a) {
            $a->category_name = $catMap[$a->category] ?? $a->category;
            $a->department_name = $deptMap[$a->department] ?? $a->department;
            $a->status_name = $statusMap[$a->status] ?? $a->status;
        }

        return view('disposals.edit', compact('disposal', 'assets'));
    }

    public function update(Request $request, AssetDisposal $disposal)
    {
        if ($disposal->status !== 'draft') {
            return back()->with('error', '只能编辑草稿报废单');
        }

        $action = $request->input('_action', 'save');

        if ($action === 'delete') {
            $disposal->delete();
            return redirect()->route('disposals.index')->with('success', '草稿已删除');
        }

        $request->validate([
            'disposal_date' => 'required|date',
            'disposal_method' => 'required|string|max:50',
            'reason' => 'nullable|string|max:500',
            'approver' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $assetIds = $request->input('asset_ids', []);

        // 更新快照
        $snapshot = [];
        foreach ($assetIds as $id) {
            $asset = Asset::find($id);
            if ($asset) {
                $snapshot[$id] = [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'name' => $asset->name,
                    'category' => $asset->category,
                    'department' => $asset->department,
                    'room' => $asset->room,
                    'user' => $asset->user,
                    'status' => $asset->status,
                    'sn' => $asset->sn,
                    'brand' => $asset->brand,
                    'model' => $asset->model,
                ];
            }
        }

        $disposal->update([
            'disposal_date' => $request->disposal_date,
            'disposal_method' => $request->disposal_method,
            'reason' => $request->reason,
            'approver' => $request->approver,
            'remarks' => $request->remarks,
            'draft_data' => [
                'asset_ids' => $assetIds,
                'snapshot' => $snapshot,
            ],
        ]);

        if ($action === 'submit') {
            $disposal->update([
                'order_no' => AssetDisposal::generateOrderNo(),
                'status' => 'active',
            ]);
            $this->applyDisposal($assetIds);
            return redirect()->route('disposals.index')->with('success', "报废单 {$disposal->order_no} 已生效，共报废 " . count($assetIds) . " 项资产");
        }

        return back()->with('success', '草稿已更新');
    }

    public function show(AssetDisposal $disposal)
    {
        $assetIds = $disposal->draft_data['asset_ids'] ?? [];
        $snapshot = $disposal->draft_data['snapshot'] ?? [];

        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');
        $statusMap = DepartmentCode::type('status')->pluck('name', 'code');

        $assets = Asset::whereIn('id', $assetIds)->get();
        foreach ($assets as $a) {
            $a->category_name = $catMap[$a->category] ?? $a->category;
            $a->department_name = $deptMap[$a->department] ?? $a->department;
            $a->status_name = $statusMap[$a->status] ?? $a->status;
        }

        return view('disposals.show', compact('disposal', 'assets', 'snapshot'));
    }

    public function destroy(AssetDisposal $disposal)
    {
        $disposal->delete();
        return redirect()->route('disposals.index')->with('success', '报废单已删除');
    }

    public function cancel(Request $request)
    {
        $disposal = AssetDisposal::findOrFail($request->id);
        if ($disposal->status === 'cancelled') {
            return back()->with('error', '该报废单已经作废');
        }

        $assetIds = $disposal->draft_data['asset_ids'] ?? [];
        $snapshot = $disposal->draft_data['snapshot'] ?? [];

        // 恢复资产状态
        foreach ($assetIds as $id) {
            $asset = Asset::find($id);
            if (!$asset) continue;
            $prevStatus = $snapshot[$id]['status'] ?? 'XZ';
            $asset->update(['status' => $prevStatus]);
        }

        $disposal->update(['status' => 'cancelled']);

        return redirect()->route('disposals.index')->with('success', '报废单已作废，资产状态已恢复');
    }

    private function applyDisposal(array $assetIds): void
    {
        foreach ($assetIds as $id) {
            $asset = Asset::find($id);
            if ($asset) {
                $asset->update(['status' => 'BF']);
            }
        }
    }
}
