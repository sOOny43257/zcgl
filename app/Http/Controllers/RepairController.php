<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\Repair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RepairController extends Controller
{
    public function index(Request $request)
    {
        $query = Repair::with('asset');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('fault_description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhereHas('asset', function ($aq) use ($search) {
                      $aq->where('asset_code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->get('date_from')) {
            $query->whereDate('repair_date', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('repair_date', '<=', $to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $repairs = $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return view('repairs.index', compact('repairs'));
    }

    public function create(Request $request)
    {
        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::find($request->asset_id);
        }
        $assets = $asset ? [$asset] : [];
        return view('repairs.create', compact('asset', 'assets'));
    }

    public function store(Request $request)
    {
        $action = $request->input('_action', 'draft');

        $rules = [
            'repair_date' => 'required|date',
            'fault_category' => 'nullable|string|max:20',
            'fault_description' => 'nullable|string|max:2000',
            'repair_method' => 'nullable|string|max:20',
            'vendor' => 'nullable|string|max:200',
            'cost' => 'nullable|numeric|min:0',
            'expected_completion_date' => 'nullable|date|after_or_equal:repair_date',
            'remarks' => 'nullable|string|max:1000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ];

        if ($action === 'submit') {
            $rules['asset_ids'] = 'required|array|min:1';
            $rules['asset_ids.*'] = 'exists:assets,id';
            $rules['fault_description'] = 'required|string|max:2000';
        } else {
            $rules['asset_ids'] = 'nullable|array';
            $rules['asset_ids.*'] = 'exists:assets,id';
        }

        $request->validate($rules);

        // 处理附件
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('repairs', 'public');
                $attachmentPaths[] = $path;
            }
        }

        $assetIds = $request->input('asset_ids', []);
        $status = ($action === 'submit') ? 'submitted' : 'draft';
        $repairs = [];

        foreach ($assetIds as $assetId) {
            $orderNo = ($action === 'submit') ? Repair::generateOrderNo() : null;
            $previousAssetStatus = null;

            if ($action === 'submit') {
                $asset = Asset::findOrFail($assetId);
                $previousAssetStatus = $asset->status;
                $asset->update(['status' => 'WX']);

                AssetLog::create([
                    'asset_id' => $asset->id,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'field' => 'status',
                    'field_label' => '状态',
                    'old_value' => $previousAssetStatus,
                    'new_value' => 'WX',
                    'created_at' => now(),
                ]);
            }

            $repairs[] = Repair::create([
                'order_no' => $orderNo,
                'asset_id' => $assetId,
                'repair_date' => $request->repair_date,
                'fault_category' => $request->fault_category,
                'fault_description' => $request->fault_description,
                'repair_method' => $request->repair_method,
                'vendor' => $request->vendor,
                'cost' => $request->cost,
                'expected_completion_date' => $request->expected_completion_date,
                'operator' => auth()->user()->name,
                'status' => $status,
                'previous_asset_status' => $previousAssetStatus,
                'remarks' => $request->remarks,
                'attachments' => $attachmentPaths ?: null,
            ]);
        }

        if ($action === 'submit') {
            $count = count($repairs);
            return redirect()->route('repairs.index')->with('success', "已提交 {$count} 张维修单");
        }

        return redirect()->route('repairs.edit', $repairs[0])->with('success', '草稿已保存');
    }

    public function show(Repair $repair)
    {
        $repair->load('asset');
        return view('repairs.show', compact('repair'));
    }

    public function edit(Repair $repair)
    {
        if ($repair->status !== 'draft') {
            return redirect()->route('repairs.index')->with('error', '只能编辑草稿维修单');
        }

        return view('repairs.edit', compact('repair'));
    }

    public function update(Request $request, Repair $repair)
    {
        if ($repair->status !== 'draft') {
            return back()->with('error', '只能编辑草稿维修单');
        }

        $action = $request->input('_action', 'save');

        if ($action === 'delete') {
            $this->deleteAttachmentFiles($repair);
            $repair->delete();
            return redirect()->route('repairs.index')->with('success', '草稿已删除');
        }

        $rules = [
            'repair_date' => 'required|date',
            'fault_category' => 'nullable|string|max:20',
            'fault_description' => 'nullable|string|max:2000',
            'repair_method' => 'nullable|string|max:20',
            'vendor' => 'nullable|string|max:200',
            'cost' => 'nullable|numeric|min:0',
            'expected_completion_date' => 'nullable|date|after_or_equal:repair_date',
            'remarks' => 'nullable|string|max:1000',
            'attachments.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip,rar',
        ];

        if ($action === 'submit') {
            $rules['asset_ids'] = 'required|array|min:1'; $rules['asset_ids.*'] = 'exists:assets,id';
            $rules['fault_description'] = 'required|string|max:2000';
        } else {
            $rules['asset_ids'] = 'nullable|array'; $rules['asset_ids.*'] = 'exists:assets,id';
        }

        $request->validate($rules);

        // 处理新附件
        $attachmentPaths = $repair->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('repairs', 'public');
                $attachmentPaths[] = $path;
            }
        }

        // 处理删除标记的附件
        $removeAttachments = $request->input('remove_attachments', []);
        if (!empty($removeAttachments)) {
            foreach ($removeAttachments as $rmPath) {
                if (Storage::disk('public')->exists($rmPath)) {
                    Storage::disk('public')->delete($rmPath);
                }
                $attachmentPaths = array_filter($attachmentPaths, fn($p) => $p !== $rmPath);
            }
            $attachmentPaths = array_values($attachmentPaths);
        }

        $repair->update([
            'asset_id' => ($request->input('asset_ids', [])[0] ?? $repair->asset_id),
            'repair_date' => $request->repair_date,
            'fault_category' => $request->fault_category,
            'fault_description' => $request->fault_description,
            'repair_method' => $request->repair_method,
            'vendor' => $request->vendor,
            'cost' => $request->cost,
            'expected_completion_date' => $request->expected_completion_date,
            'remarks' => $request->remarks,
            'attachments' => !empty($attachmentPaths) ? $attachmentPaths : null,
        ]);

        if ($action === 'submit') {
            $orderNo = Repair::generateOrderNo();
            $asset = Asset::findOrFail($request->input('asset_ids', [])[0] ?? $repair->asset_id);
            $previousAssetStatus = $asset->status;
            $asset->update(['status' => 'WX']);

            AssetLog::create([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'field' => 'status',
                'field_label' => '状态',
                'old_value' => $previousAssetStatus,
                'new_value' => 'WX',
                'created_at' => now(),
            ]);

            $repair->update([
                'order_no' => $orderNo,
                'status' => 'submitted',
                'operator' => auth()->user()->name,
                'previous_asset_status' => $previousAssetStatus,
            ]);

            return redirect()->route('repairs.index')->with('success', "维修单 {$orderNo} 已提交");
        }

        return back()->with('success', '草稿已更新');
    }

    public function complete(Request $request, Repair $repair)
    {
        if (!in_array($repair->status, ['submitted', 'in_progress'])) {
            return back()->with('error', '当前状态不允许完成操作');
        }

        $request->validate([
            'actual_completion_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);

        // 恢复资产状态
        $asset = $repair->asset;
        if ($asset && $repair->previous_asset_status) {
            $oldStatus = $asset->status;
            $newStatus = $repair->previous_asset_status;
            $asset->update(['status' => $newStatus]);

            AssetLog::create([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'field' => 'status',
                'field_label' => '状态',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'created_at' => now(),
            ]);
        }

        $repair->update([
            'status' => 'completed',
            'actual_completion_date' => $request->actual_completion_date ?? now(),
            'cost' => $request->cost ?? $repair->cost,
            'remarks' => $request->remarks ?? $repair->remarks,
        ]);

        return redirect()->route('repairs.show', $repair)->with('success', '维修单已完成，资产状态已恢复');
    }

    public function cancel(Request $request)
    {
        $repair = Repair::findOrFail($request->input('id'));

        if (!in_array($repair->status, ['submitted', 'in_progress'])) {
            return back()->with('error', '当前状态不允许作废');
        }

        // 恢复资产状态
        $asset = $repair->asset;
        if ($asset && $repair->previous_asset_status) {
            $oldStatus = $asset->status;
            $newStatus = $repair->previous_asset_status;
            $asset->update(['status' => $newStatus]);

            AssetLog::create([
                'asset_id' => $asset->id,
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name,
                'field' => 'status',
                'field_label' => '状态',
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'created_at' => now(),
            ]);
        }

        $repair->update(['status' => 'cancelled']);

        return redirect()->route('repairs.index')->with('success', '维修单已作废，资产状态已恢复');
    }

    public function destroy(Repair $repair)
    {
        if (in_array($repair->status, ['submitted', 'in_progress'])) {
            return back()->with('error', '已提交的维修单不能直接删除，请先作废');
        }

        $this->deleteAttachmentFiles($repair);
        $repair->delete();

        return redirect()->route('repairs.index')->with('success', '维修单已删除');
    }

    private function deleteAttachmentFiles(Repair $repair): void
    {
        foreach ($repair->attachments ?? [] as $path) {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}
