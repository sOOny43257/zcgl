<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\TransferOrder;
use Illuminate\Http\Request;

class TransferOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->syncFromLogs();

        $query = TransferOrder::with('asset');

        // 模糊搜索
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhereHas('asset', function ($aq) use ($search) {
                      $aq->where('asset_code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        // 日期范围
        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $transfers = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        // 为每个调拨单构建展开详情（批量修改的资产列表）
        $labelMap = [
            'department' => '部门', 'room' => '房间号', 'ip' => 'IP地址',
            'mac' => 'MAC地址', 'sn' => 'SN序列号', 'brand' => '品牌',
            'model' => '规格型号', 'category' => '类别', 'user' => '使用人',
            'status' => '状态', 'remarks' => '备注',
            'financial_code' => '财务编码',
        ];

        $transfers->getCollection()->transform(function ($t) use ($labelMap) {
            $draft = $t->draft_data;
            $assetIds = $draft['asset_ids'] ?? [$t->asset_id];
            $changes = $draft['changes'] ?? [];
            $originals = $draft['original'] ?? [];

            if (empty($assetIds) || (count($assetIds) === 1 && $assetIds[0] == $t->asset_id && empty($changes))) {
                $t->detail_items = [];
                $t->detail_count = 0;
                return $t;
            }

            $assets = \App\Models\Asset::whereIn('id', $assetIds)->get()->keyBy('id');
            $items = [];

            foreach ($assetIds as $id) {
                $asset = $assets[$id] ?? null;
                if (!$asset) continue;
                $row = ['asset' => $asset, 'changes' => []];
                $ac = $changes[(string)$id] ?? $changes[$id] ?? [];
                $orig = $originals[(string)$id] ?? $originals[$id] ?? [];
                foreach ($ac as $field => $newValue) {
                    if ($newValue === null || $newValue === '') continue;
                    $oldValue = $orig[$field] ?? ($asset->$field ?? '');
                    if ((string)$oldValue === (string)$newValue) continue;
                    $row['changes'][] = [
                        'label' => $labelMap[$field] ?? $field,
                        'field' => $field,
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
                $items[] = $row;
            }

            $t->detail_items = $items;
            $t->detail_count = count($assetIds);
            return $t;
        });

        return view('transfers.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $preselectIds = $request->get('assets', '');
        return view('transfers.create', compact('preselectIds'));
    }

    public function store(Request $request)
    {
        $action = $request->input('_action', 'draft');
        $assetIds = $request->input('asset_ids', []);
        $changes = $this->parseChanges($request->input('changes', []));

        if (empty($assetIds)) {
            return back()->with('error', '请至少选择一个资产');
        }

        $hasChanges = !empty($changes);
        if ($action === 'submit' && !$hasChanges) {
            return back()->with('error', '提交前请至少修改一项资产数据');
        }

        // 仅在提交时生成调拨单号
        $orderNo = null;
        $status = 'draft';
        if ($action === 'submit') {
            $orderNo = $this->generateOrderNo();
            $status = 'active';
        }

        $transfer = TransferOrder::create([
            'order_no' => $orderNo,
            'asset_id' => $assetIds[0],
            'log_ids' => [],
            'from_dept' => '',
            'to_dept' => '',
            'operator' => auth()->user()->name,
            'status' => $status,
            'reason' => $request->input('reason', ''),
            'draft_data' => [
                'asset_ids' => $assetIds,
                'original' => Asset::whereIn('id', $assetIds)->get()->keyBy('id')->toArray(),
                'changes' => $changes,
            ],
        ]);

        if ($action === 'submit') {
            $this->applyChanges($transfer, $changes);
            return redirect()->route('transfers.index')->with('success', "调拨单 {$orderNo} 已生效");
        }

        return redirect()->route('transfers.edit', $transfer)->with('success', '草稿已保存，请双击单元格修改数据');
    }

    public function edit(TransferOrder $transferOrder)
    {
        if ($transferOrder->status !== 'draft') {
            return redirect()->route('transfers.index')->with('error', '只能编辑待提交的调拨单');
        }

        $draft = $transferOrder->draft_data;
        $assetIds = $draft['asset_ids'] ?? [];
        $assets = Asset::whereIn('id', $assetIds)->get();
        $changes = $draft['changes'] ?? [];

        // 附加中文名
        $deptMap = \App\Models\DepartmentCode::type('department')->pluck('name', 'code');
        $catMap = \App\Models\DepartmentCode::type('category')->pluck('name', 'code');
        $statusMap = \App\Models\DepartmentCode::type('status')->pluck('name', 'code');
        foreach ($assets as $a) {
            $a->department_name = $deptMap[$a->department] ?? $a->department;
            $a->category_name = $catMap[$a->category] ?? $a->category;
            $a->status_name = $statusMap[$a->status] ?? $a->status;
        }

        $reason = $transferOrder->reason ?? '';

        return view('transfers.edit', compact('transferOrder', 'assets', 'changes', 'reason'));
    }

    public function update(Request $request, TransferOrder $transferOrder)
    {
        if ($transferOrder->status !== 'draft') {
            return back()->with('error', '只能编辑待提交的调拨单');
        }

        $action = $request->input('_action', 'save');
        $changes = $this->parseChanges($request->input('changes', []));

        if ($action === 'delete') {
            $transferOrder->delete();
            return redirect()->route('transfers.index')->with('success', '草稿已删除');
        }

        if ($action === 'submit' && empty($changes)) {
            return back()->with('error', '请至少修改一项资产数据后再提交');
        }

        $draft = $transferOrder->draft_data;
        $draft['changes'] = $changes;
        $transferOrder->draft_data = $draft;
        $transferOrder->reason = $request->input('reason', '');

        if ($action === 'submit') {
            // 提交时生成调拨单号
            $transferOrder->order_no = $this->generateOrderNo();
            $transferOrder->status = 'active';
            $this->applyChanges($transferOrder, $changes);
            $transferOrder->save();
            return redirect()->route('transfers.index')->with('success', "调拨单 {$transferOrder->order_no} 已生效");
        }

        $transferOrder->save();
        return back()->with('success', '草稿已更新');
    }

    private function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'DB-' . $today . '-';
        $last = TransferOrder::where('order_no', 'like', $prefix . '%')
            ->orderBy('order_no', 'desc')
            ->value('order_no');
        $count = $last ? (int) substr($last, -3) + 1 : 1;
        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    private function parseChanges($changes): array
    {
        if (is_string($changes)) {
            $decoded = json_decode($changes, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($changes) ? $changes : [];
    }

    private function applyChanges(TransferOrder $transfer, array $changes): void
    {
        $logIds = [];
        foreach ($changes as $assetId => $fields) {
            if (!is_array($fields) || empty($fields)) continue;
            $asset = Asset::find($assetId);
            if (!$asset) continue;

            foreach ($fields as $field => $newValue) {
                $oldValue = $asset->$field;
                if ($oldValue == $newValue) continue;
                $asset->$field = $newValue;
            }
            $asset->save();

            $latestLogs = AssetLog::where('asset_id', $assetId)
                ->orderByDesc('created_at')
                ->take(count($fields))
                ->pluck('id')
                ->toArray();
            $logIds = array_merge($logIds, $latestLogs);
        }

        $transfer->log_ids = $logIds;
        $transfer->save();
    }

    public function show(TransferOrder $transferOrder)
    {
        $draft = $transferOrder->draft_data;
        $assetIds = $draft['asset_ids'] ?? [$transferOrder->asset_id];
        $assets = Asset::whereIn('id', $assetIds)->get()->keyBy('id');
        $changes = $draft['changes'] ?? [];
        $originals = $draft['original'] ?? []; // 草稿创建时的快照

        $items = [];
        $labelMap = [
            'department' => '部门', 'room' => '房间号', 'ip' => 'IP地址',
            'mac' => 'MAC地址', 'sn' => 'SN序列号', 'brand' => '品牌',
            'model' => '规格型号', 'category' => '类别', 'user' => '使用人',
            'status' => '状态', 'remarks' => '备注',
            'financial_code' => '财务编码',
        ];

        foreach ($assetIds as $id) {
            $asset = $assets[$id] ?? null;
            if (!$asset) continue;
            $row = ['asset' => $asset, 'changes' => []];
            $ac = $changes[(string)$id] ?? $changes[$id] ?? [];
            $orig = $originals[(string)$id] ?? $originals[$id] ?? [];
            foreach ($ac as $field => $newValue) {
                if ($newValue === null || $newValue === '') continue;
                // 从草稿快照取原始值（因为资产已被 applyChanges 更新）
                $oldValue = $orig[$field] ?? ($asset->$field ?? '');
                if ((string)$oldValue === (string)$newValue) continue;
                $row['changes'][] = [
                    'field' => $field,
                    'label' => $labelMap[$field] ?? $field,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
            $items[] = $row;
        }

        return view('transfers.show', compact('transferOrder', 'items'));
    }


    public function snapshot(TransferOrder $transferOrder)
    {
        $draft = $transferOrder->draft_data ?: [];
        $assetIds = $draft['asset_ids'] ?? [$transferOrder->asset_id];
        $assets = Asset::whereIn('id', $assetIds)->get()->keyBy('id');
        $changes = $draft['changes'] ?? [];
        $originals = $draft['original'] ?? [];

        $labelMap = [
            'department' => '部门', 'room' => '房间号', 'ip' => 'IP地址',
            'mac' => 'MAC地址', 'sn' => 'SN序列号', 'brand' => '品牌',
            'model' => '规格型号', 'category' => '类别', 'user' => '使用人',
            'status' => '状态', 'remarks' => '备注',
            'financial_code' => '财务编码',
        ];

        $items = [];
        foreach ($assetIds as $id) {
            $asset = $assets[$id] ?? null;
            if (!$asset) continue;
            $row = [
                'asset_code' => $asset->asset_code,
                'financial_code' => $asset->financial_code,
                'name' => $asset->name,
                'changes' => [],
            ];
            $ac = $changes[(string)$id] ?? $changes[$id] ?? [];
            $orig = $originals[(string)$id] ?? $originals[$id] ?? [];
            foreach ($ac as $field => $newValue) {
                if ($newValue === null || $newValue === '') continue;
                $oldValue = $orig[$field] ?? ($asset->$field ?? '');
                if ((string)$oldValue === (string)$newValue) continue;
                $row['changes'][] = [
                    'label' => $labelMap[$field] ?? $field,
                    'old' => $this->translateField($field, $oldValue),
                    'new' => $this->translateField($field, $newValue),
                ];
            }
            $items[] = $row;
        }

        return response()->json([
            'id' => $transferOrder->id,
            'order_no' => $transferOrder->order_no,
            'operator' => $transferOrder->operator,
            'status' => $transferOrder->status,
            'reason' => $transferOrder->reason,
            'created_at' => $transferOrder->created_at->format('Y-m-d H:i:s'),
            'cancelled_at' => $transferOrder->cancelled_at ? $transferOrder->cancelled_at->format('Y-m-d H:i:s') : null,
            'is_cancelled' => $transferOrder->is_cancelled,
            'items' => $items,
            'itemCount' => count($items),
        ]);
    }

    private function translateField($field, $value)
    {
        if ($value === null || $value === '') return $value;
        return match($field) {
            'department' => Asset::translateDept($value),
            'category' => Asset::translateCat($value),
            'status' => Asset::translateStatus($value),
            default => $value,
        };
    }

    public function destroy(TransferOrder $transferOrder)
    {
        $transferOrder->delete();
        return redirect()->route('transfers.index')->with('success', '调拨单已删除');
    }

    public function cancel(Request $request)
    {
        $transfer = TransferOrder::findOrFail($request->id);
        if ($transfer->status === 'cancelled') {
            return back()->with('error', '该调拨单已经作废');
        }

        $asset = Asset::findOrFail($transfer->asset_id);
        $logs = AssetLog::whereIn('id', $transfer->log_ids ?? [])->get();
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

        // 记下当前最大日志ID，修改变更后捕获新产生的日志
        $maxLogId = AssetLog::max('id') ?? 0;
        $asset->save();
        $newLogIds = AssetLog::where('id', '>', $maxLogId)->pluck('id')->toArray();

        // 把新日志也加入此调拨单，防止 syncFromLogs 误识别为"新调拨"
        $currentLogIds = $transfer->log_ids ?? [];
        $transfer->update([
            'status' => 'cancelled',
            'is_cancelled' => true,
            'cancelled_at' => now(),
            'log_ids' => array_merge($currentLogIds, $newLogIds),
        ]);

        return redirect()->route('transfers.index')->with('success', '调拨单已作废：' . implode('；', $changes));
    }

    private function syncFromLogs(): void
    {
        $existingLogIds = TransferOrder::withTrashed()->pluck('log_ids')->flatten()->filter()->toArray();
        $logs = AssetLog::whereIn('field', ['department', 'user'])
            ->whereNotIn('id', $existingLogIds)
            ->with('asset')->orderBy('created_at', 'desc')->get();

        $groups = $logs->groupBy(fn($log) => $log->asset_id . '_' . $log->created_at->format('YmdHis'));

        foreach ($groups as $group) {
            $first = $group->first();
            if (!$first->asset) continue;
            $deptLog = $group->firstWhere('field', 'department');
            $userLog = $group->firstWhere('field', 'user');
            TransferOrder::create([
                'order_no' => $this->generateOrderNo(),
                'asset_id' => $first->asset->id,
                'log_ids' => $group->pluck('id')->toArray(),
                'from_dept' => $deptLog?->old_value,
                'to_dept' => $deptLog?->new_value,
                'from_user' => $userLog?->old_value,
                'to_user' => $userLog?->new_value,
                'operator' => $first->user_name,
                'status' => 'active',
                'created_at' => $first->created_at,
                'updated_at' => $first->created_at,
            ]);
        }
    }
}
