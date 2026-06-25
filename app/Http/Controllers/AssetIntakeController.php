<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetIntake;
use App\Models\DepartmentCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetIntakeController extends Controller
{
    public function index(Request $request)
    {
        $query = AssetIntake::with('assets');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('supplier', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('approver', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('date_from')) {
            $query->whereDate('intake_date', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('intake_date', '<=', $to);
        }

        $perPage = min((int) $request->get('per_page', 10), 100);
        $intakes = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('intakes.index', compact('intakes'));
    }

    public function create()
    {
        $intake = null;
        return view('intakes.create', compact('intake'));
    }

    public function store(Request $request)
    {
        $action = $request->input('_action', 'draft');

        $request->validate([
            'intake_date' => 'required|date',
            'supplier' => 'nullable|string|max:200',
            'purchase_order_no' => 'nullable|string|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'approver' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'description' => 'nullable|string|max:2000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:200',
            'items.*.category' => 'nullable|string|max:50',
            'items.*.brand' => 'nullable|string|max:100',
            'items.*.model' => 'nullable|string|max:100',
            'items.*.sn' => 'nullable|string|max:200',
            'items.*.financial_code' => 'nullable|string|max:50',
            'items.*.department' => 'nullable|string|max:100',
            'items.*.room' => 'nullable|string|max:50',
            'items.*.ip' => 'nullable|string|max:45',
            'items.*.mac' => 'nullable|string|max:17',
            'items.*.user' => 'nullable|string|max:100',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        $items = $request->input('items', []);

        // 提交时校验总金额与明细合计是否一致
        if ($action === 'submit' && $request->filled('total_amount')) {
            $itemSum = collect($items)->sum(fn($it) => (float) ($it['purchase_price'] ?? 0));
            $totalAmount = (float) $request->input('total_amount');
            if (abs($itemSum - $totalAmount) > 0.01) {
                return back()->withInput()->with('error', "总金额（{$totalAmount}）与明细合计（{$itemSum}）不一致，无法提交");
            }
        }

        $status = 'draft';
        $orderNo = null;

        if ($action === 'submit') {
            $orderNo = AssetIntake::generateOrderNo();
            $status = 'active';
        }

        // 处理附件上传
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('intakes', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $intake = AssetIntake::create([
            'order_no' => $orderNo,
            'intake_date' => $request->intake_date,
            'supplier' => $request->supplier,
            'purchase_order_no' => $request->purchase_order_no,
            'total_amount' => $request->total_amount,
            'operator' => auth()->user()->name,
            'approver' => $request->approver,
            'status' => $status,
            'draft_data' => ['items' => $items],
            'remarks' => $request->remarks,
            'description' => $request->description,
            'attachments' => $attachmentPaths ?: null,
        ]);

        if ($action === 'submit') {
            $this->createAssetsFromIntake($intake, $items);
            return redirect()->route('intakes.index')->with('success', "入库单 {$orderNo} 已生效，共入库 " . count($items) . " 项资产");
        }

        return redirect()->route('intakes.edit', $intake)->with('success', '草稿已保存');
    }

    public function edit(AssetIntake $intake)
    {
        if ($intake->status !== 'draft') {
            return redirect()->route('intakes.index')->with('error', '只能编辑草稿入库单');
        }

        return view('intakes.edit', compact('intake'));
    }

    public function update(Request $request, AssetIntake $intake)
    {
        if ($intake->status !== 'draft') {
            return back()->with('error', '只能编辑草稿入库单');
        }

        $action = $request->input('_action', 'save');

        if ($action === 'delete') {
            // 删除附件文件
            $this->deleteAttachmentFiles($intake);
            $intake->delete();
            return redirect()->route('intakes.index')->with('success', '草稿已删除');
        }

        $request->validate([
            'intake_date' => 'required|date',
            'supplier' => 'nullable|string|max:200',
            'purchase_order_no' => 'nullable|string|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'approver' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'description' => 'nullable|string|max:2000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:200',
        ]);

        $items = $request->input('items', []);

        // 提交时校验总金额与明细合计是否一致
        if ($action === 'submit' && $request->filled('total_amount')) {
            $itemSum = collect($items)->sum(fn($it) => (float) ($it['purchase_price'] ?? 0));
            $totalAmount = (float) $request->input('total_amount');
            if (abs($itemSum - $totalAmount) > 0.01) {
                return back()->withInput()->with('error', "总金额（{$totalAmount}）与明细合计（{$itemSum}）不一致，无法提交");
            }
        }

        // 处理新附件上传（追加到已有附件）
        $attachmentPaths = $intake->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('intakes', 'public');
                $attachmentPaths[] = $path;
            }
        }

        // 处理删除标记的附件
        $removeAttachments = $request->input('remove_attachments', []);
        if (!empty($removeAttachments)) {
            foreach ($removeAttachments as $rmPath) {
                // 删除文件
                if (Storage::disk('public')->exists($rmPath)) {
                    Storage::disk('public')->delete($rmPath);
                }
                $attachmentPaths = array_filter($attachmentPaths, fn($p) => $p !== $rmPath);
            }
            $attachmentPaths = array_values($attachmentPaths);
        }

        $intake->update([
            'intake_date' => $request->intake_date,
            'supplier' => $request->supplier,
            'purchase_order_no' => $request->purchase_order_no,
            'total_amount' => $request->total_amount,
            'approver' => $request->approver,
            'remarks' => $request->remarks,
            'description' => $request->description,
            'draft_data' => ['items' => $items],
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
        ]);

        if ($action === 'submit') {
            $intake->update([
                'order_no' => AssetIntake::generateOrderNo(),
                'status' => 'active',
            ]);
            $this->createAssetsFromIntake($intake, $items);
            return redirect()->route('intakes.index')->with('success', "入库单 {$intake->order_no} 已生效，共入库 " . count($items) . " 项资产");
        }

        return back()->with('success', '草稿已更新');
    }

    public function show(AssetIntake $intake)
    {
        $items = $intake->draft_data['items'] ?? [];
        $catMap = DepartmentCode::type('category')->pluck('name', 'code');
        $deptMap = DepartmentCode::type('department')->pluck('name', 'code');

        $createdAssets = collect();
        if ($intake->status === 'active') {
            $createdAssets = $intake->assets()->get();
        }

        return view('intakes.show', compact('intake', 'items', 'catMap', 'deptMap', 'createdAssets'));
    }

    public function destroy(AssetIntake $intake)
    {
        $this->deleteAttachmentFiles($intake);
        $intake->delete();
        return redirect()->route('intakes.index')->with('success', '入库单已删除');
    }

    public function cancel(Request $request)
    {
        $intake = AssetIntake::findOrFail($request->id);
        if ($intake->status === 'cancelled') {
            return back()->with('error', '该入库单已经作废');
        }

        $intake->update(['status' => 'cancelled']);

        return redirect()->route('intakes.index')->with('success', '入库单已作废（已入库的资产不受影响）');
    }

    private function deleteAttachmentFiles(AssetIntake $intake): void
    {
        foreach ($intake->attachments ?? [] as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function createAssetsFromIntake(AssetIntake $intake, array $items): void
    {
        foreach ($items as $item) {
            $dept = $item['department'] ?? '';
            $cat = $item['category'] ?? '台式计算机（非国产）';

            if ($dept) {
                $deptCode = DepartmentCode::type('department')->where('name', $dept)->first();
                if ($deptCode) $dept = $deptCode->code;
            }
            if ($cat) {
                $catCode = DepartmentCode::type('category')->where('name', $cat)->first();
                if ($catCode) $cat = $catCode->code;
            }

            Asset::create([
                'name' => $item['name'] ?? '',
                'financial_code' => $item['financial_code'] ?? '',
                'department' => $dept,
                'room' => $item['room'] ?? '',
                'ip' => !empty($item['ip']) ? $item['ip'] : null,
                'mac' => !empty($item['mac']) ? $item['mac'] : null,
                'sn' => $item['sn'] ?? '',
                'brand' => $item['brand'] ?? '',
                'model' => $item['model'] ?? '',
                'category' => $cat,
                'status' => 'ZY',
                'user' => $item['user'] ?? '',
                'remarks' => $item['remarks'] ?? '',
                'purchase_date' => $intake->intake_date,
                'purchase_price' => $item['purchase_price'] ?? null,
                'supplier' => $intake->supplier,
                'warranty_date' => !empty($item['warranty_date']) ? $item['warranty_date'] : null,
                'intake_id' => $intake->id,
            ]);
        }
    }
}
