<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\TransferOrder;
use Illuminate\Http\Request;

class TransferOrderController extends Controller
{
    public function index()
    {
        // 同步：从 asset_logs 生成调拨单（幂等：已存在的跳过）
        $this->syncFromLogs();

        $transfers = TransferOrder::with('asset')->orderBy('created_at', 'desc')->paginate(30);
        return view('transfers.index', compact('transfers'));
    }

    public function show(TransferOrder $transferOrder)
    {
        $transferOrder->load('asset');
        return view('transfers.show', compact('transferOrder'));
    }

    public function cancel(Request $request)
    {
        $transfer = TransferOrder::findOrFail($request->id);
        if ($transfer->is_cancelled) {
            return back()->with('error', '该调拨单已经作废');
        }

        $asset = Asset::findOrFail($transfer->asset_id);
        $logs = AssetLog::whereIn('id', $transfer->log_ids)->get();
        $changes = [];

        foreach ($logs as $log) {
            if ($log->field === 'department') {
                $asset->department = $log->old_value;
                $changes[] = "部门: {$log->new_value} → {$log->old_value}";
            } elseif ($log->field === 'user') {
                $asset->user = $log->old_value;
                $changes[] = "使用人: {$log->new_value} → {$log->old_value}";
            }
        }

        $asset->save(); // 触发变更追踪
        $transfer->update(['is_cancelled' => true, 'cancelled_at' => now()]);

        return redirect()->route('transfers.index')
            ->with('success', '调拨单已作废：' . implode('；', $changes));
    }

    private function syncFromLogs(): void
    {
        // 获取未关联到 transfer_orders 的资产变更日志
        $existingLogIds = TransferOrder::pluck('log_ids')->flatten()->toArray();

        $logs = AssetLog::whereIn('field', ['department', 'user'])
            ->whereNotIn('id', $existingLogIds)
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->get();

        $groups = $logs->groupBy(function ($log) {
            return $log->asset_id . '_' . $log->created_at->format('YmdHis');
        });

        $today = now()->format('Ymd');
        $baseCount = TransferOrder::whereDate('created_at', now())->count();

        $i = 0;
        foreach ($groups as $group) {
            $first = $group->first();
            $asset = $first->asset;
            if (!$asset) continue;

            $deptLog = $group->firstWhere('field', 'department');
            $userLog = $group->firstWhere('field', 'user');

            $orderNo = 'DB-' . $today . '-' . str_pad($baseCount + $i + 1, 3, '0', STR_PAD_LEFT);
            $i++;

            TransferOrder::create([
                'order_no' => $orderNo,
                'asset_id' => $asset->id,
                'log_ids' => $group->pluck('id')->toArray(),
                'from_dept' => $deptLog ? $deptLog->old_value : null,
                'to_dept' => $deptLog ? $deptLog->new_value : null,
                'from_user' => $userLog ? $userLog->old_value : null,
                'to_user' => $userLog ? $userLog->new_value : null,
                'operator' => $first->user_name,
                'created_at' => $first->created_at,
                'updated_at' => $first->created_at,
            ]);
        }
    }
}
