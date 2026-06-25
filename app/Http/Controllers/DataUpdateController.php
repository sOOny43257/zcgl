<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\DataSubmission;
use Illuminate\Http\Request;

class DataUpdateController extends Controller
{
    // 接收 shell 脚本提交（无需登录）
    public function receive(Request $request)
    {
        $data = $request->only(['name', 'department', 'room', 'ip', 'mac', 'sn']);
        $data['status'] = 'pending';

        // 预验证
        $errors = [];
        $suggestions = [];

        foreach (['department', 'room', 'ip', 'mac', 'sn'] as $f) {
            if (empty($data[$f])) {
                $errors[$f] = '必填字段为空';
            }
        }

        // 验证部门
        if (!empty($data['department'])) {
            $match = $this->findBestMatch($data['department'], 'department');
            if ($match['code'] !== $data['department']) {
                $errors['department'] = '编码未匹配';
                $suggestions['department'] = $match;
            }
        }

        $data['errors'] = $errors;
        $data['suggestions'] = $suggestions;

        DataSubmission::create($data);

        return response()->json(['success' => true, 'message' => '数据已接收']);
    }

    // 管理页面
    public function index()
    {
        $submissions = DataSubmission::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('data-updates.index', compact('submissions'));
    }

    // 智能建议（AJAX）
    public function suggest(Request $request)
    {
        $field = $request->get('field');
        $value = $request->get('value');

        $typeMap = ['department' => 'department', 'category' => 'category', 'status' => 'status'];
        $type = $typeMap[$field] ?? null;

        if (!$type || empty($value)) {
            return response()->json(['suggestion' => null, 'match' => false]);
        }

        $match = $this->findBestMatch($value, $type);

        return response()->json([
            'suggestion' => $match,
            'match' => $match['code'] === $value || $match['similarity'] >= 80,
        ]);
    }

    // 更新字段（AJAX）
    public function updateField(Request $request)
    {
        $sub = DataSubmission::findOrFail($request->id);
        $field = $request->field;
        $value = $request->value;

        $sub->$field = $value;

        // 重新验证该字段
        $errors = $sub->errors ?? [];
        if ($field === 'department') {
            $match = $this->findBestMatch($value, 'department');
            if ($match['code'] === $value || $match['similarity'] >= 90) {
                unset($errors['department']);
                $suggestions = $sub->suggestions ?? [];
                unset($suggestions['department']);
                $sub->suggestions = $suggestions;
            } else {
                $errors['department'] = '编码未匹配';
            }
        }
        $sub->errors = $errors;
        $sub->save();

        return response()->json(['success' => true]);
    }

    // 提交单行
    public function submitRow(Request $request)
    {
        $sub = DataSubmission::findOrFail($request->id);

        // 最终验证
        if (empty($sub->sn) || empty($sub->mac)) {
            return response()->json(['success' => false, 'message' => 'SN和MAC不能为空']);
        }

        $existing = Asset::where('sn', $sub->sn)->first();
        $isUpdate = $existing !== null;

        $assetData = [
            'name' => $sub->name ?: '未命名-' . $sub->sn,
            'department' => $sub->department,
            'room' => $sub->room,
            'ip' => $sub->ip ?: '0.0.0.0',
            'mac' => $sub->mac,
            'sn' => $sub->sn,
            'category' => '台式计算机（非国产）',
            'status' => 'ZY',
        ];

        if ($isUpdate) {
            // 调拨同步：如果部门变更了
            $oldDept = $existing->department;
            $existing->update($assetData);

            if ($oldDept !== $sub->department) {
                $this->syncTransfer($existing, $oldDept, $sub->department);
            }

            $sub->update(['status' => 'approved', 'submit_log' => '已更新资产 ID:' . $existing->id]);
            return response()->json(['success' => true, 'message' => "已更新资产 {$existing->asset_code}", 'action' => 'update']);
        } else {
            // IP 冲突处理
            if (Asset::where('ip', $sub->ip)->exists() && $sub->ip !== '0.0.0.0') {
                $assetData['ip'] = $sub->ip . '-dup';
            }
            $asset = Asset::create($assetData);
            $sub->update(['status' => 'approved', 'submit_log' => '已创建资产 ID:' . $asset->id]);
            return response()->json(['success' => true, 'message' => "已创建资产 {$asset->asset_code}", 'action' => 'create']);
        }
    }

    // 全部提交
    public function submitAll()
    {
        $subs = DataSubmission::where('status', 'pending')->get();
        $created = 0; $updated = 0; $failed = 0;

        foreach ($subs as $sub) {
            if (empty($sub->sn) || empty($sub->mac)) { $failed++; continue; }
            if (!empty($sub->errors)) { $failed++; continue; }

            $existing = Asset::where('sn', $sub->sn)->first();

            $assetData = [
                'name' => $sub->name ?: '未命名-' . $sub->sn,
                'department' => $sub->department,
                'room' => $sub->room,
                'ip' => $sub->ip ?: '0.0.0.0',
                'mac' => $sub->mac,
                'sn' => $sub->sn,
                'category' => '台式计算机（非国产）',
                'status' => 'ZY',
            ];

            if ($existing) {
                $oldDept = $existing->department;
                $existing->update($assetData);
                if ($oldDept !== $sub->department) {
                    $this->syncTransfer($existing, $oldDept, $sub->department);
                }
                $sub->update(['status' => 'approved', 'submit_log' => '已更新']);
                $updated++;
            } else {
                Asset::create($assetData);
                $sub->update(['status' => 'approved', 'submit_log' => '已创建']);
                $created++;
            }
        }

        return back()->with('success', "提交完成：新建 {$created} 条，更新 {$updated} 条，失败 {$failed} 条");
    }

    // 删除
    public function destroy(Request $request)
    {
        DataSubmission::findOrFail($request->id)->delete();
        return response()->json(['success' => true]);
    }

    // === 辅助方法 ===
    private function findBestMatch($value, $type): array
    {
        $codes = \App\Models\DepartmentCode::type($type)->get();
        $best = ['code' => $value, 'name' => $value, 'similarity' => 0];

        foreach ($codes as $c) {
            // 精确匹配
            if ($c->code === $value || $c->name === $value) {
                return ['code' => $c->code, 'name' => $c->name, 'similarity' => 100];
            }

            // 拼音简写匹配
            if (stripos($c->code, $value) !== false || stripos($value, $c->code) !== false) {
                $sim = max(similar_text($c->code, $value) / max(strlen($c->code), strlen($value)) * 100, 60);
                if ($sim > $best['similarity']) {
                    $best = ['code' => $c->code, 'name' => $c->name, 'similarity' => (int) $sim];
                }
            }

            // 中文名模糊匹配
            similar_text($c->name, $value, $pct);
            if ($pct > $best['similarity']) {
                $best = ['code' => $c->code, 'name' => $c->name, 'similarity' => (int) $pct];
            }
        }

        return $best;
    }

    private function syncTransfer($asset, $oldDept, $newDept): void
    {
        $today = now()->format('Ymd');
        $count = \App\Models\TransferOrder::whereDate('created_at', now())->count() + 1;
        $orderNo = 'DB-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        // 查找对应的 asset_log
        $log = \App\Models\AssetLog::where('asset_id', $asset->id)
            ->where('field', 'department')
            ->where('new_value', $newDept)
            ->orderByDesc('created_at')
            ->first();

        \App\Models\TransferOrder::create([
            'order_no' => $orderNo,
            'asset_id' => $asset->id,
            'log_ids' => $log ? [$log->id] : [],
            'from_dept' => $oldDept,
            'to_dept' => $newDept,
            'operator' => '数据更新同步',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
