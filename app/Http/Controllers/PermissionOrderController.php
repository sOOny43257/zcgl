<?php

namespace App\Http\Controllers;

use App\Models\PermissionOrder;
use App\Services\DocxPermissionExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PermissionOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = PermissionOrder::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('voided_by', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('show_voided')) {
            $query->where('status', 'voided');
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('updated_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('updated_at', '<=', $request->input('date_to'));
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $orders = $query->orderByDesc('updated_at')->paginate($perPage)->withQueryString();

        return view('permission-orders.index', compact('orders'));
    }

    public function show(PermissionOrder $permissionOrder)
    {
        return view('permission-orders.show', compact('permissionOrder'));
    }

    public function create()
    {
        return view('permission-orders.create', [
            'order' => null,
            'parsed' => old('department') ? [
                'department' => old('department'),
                'fill_date' => old('fill_date'),
                'items' => json_decode(old('items_json', '[]'), true) ?: [],
            ] : null,
        ]);
    }

    public function store(Request $request, DocxPermissionExtractor $extractor)
    {
        $action = $request->input('_action', 'draft');
        $strict = $action === 'submit';

        $rules = [
            'source_doc' => ['required', 'file', 'max:20480'],
            'department' => ['nullable', 'string', 'max:100'],
            'fill_date' => ['nullable', 'string', 'max:50'],
            'items_json' => ['nullable', 'string'],
        ];

        if ($strict) {
            $rules['department'] = ['required', 'string', 'max:100'];
            $rules['voided_by'] = ['required', 'string', 'max:100'];
            $rules['voided_at'] = ['required', 'date'];
            $rules['paper_submitted'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        $uploadedFile = $request->file('source_doc');
        $tempPath = $uploadedFile->getRealPath();
        $parsed = $extractor->extract($tempPath);

        if (empty($parsed)) {
            return back()->withInput()->with('error', '无法解析上传的 Word 表单，请确认文件是否包含标准表格模板');
        }

        $items = json_decode($request->input('items_json', '[]'), true) ?? [];
        if (! empty($items)) {
            $parsed['items'] = $items;
        }

        $order = PermissionOrder::create([
            'status' => $strict ? 'voided' : 'draft',
            'order_no' => $strict ? PermissionOrder::generateOrderNo() : null,
            'source_doc_path' => null,
            'source_file_name' => $uploadedFile->getClientOriginalName(),
            'department' => $validated['department'] ?? $parsed['department'] ?? '',
            'fill_date' => $validated['fill_date'] ?? $parsed['fill_date'] ?? '',
            'items' => $parsed['items'] ?? [],
            'voided_by' => $strict ? ($validated['voided_by'] ?? '') : null,
            'voided_at' => $strict ? ($validated['voided_at'] ?? now()) : null,
            'paper_submitted' => $strict ? $request->boolean('paper_submitted') : false,
            'paper_submitted_at' => $strict && $request->boolean('paper_submitted') ? now() : null,
            'draft_data' => $parsed,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $storedPath = $uploadedFile->storeAs('permission_orders/' . $order->id, 'source.docx', 'public');
        $order->update(['source_doc_path' => $storedPath]);

        if ($strict) {
            return redirect()->route('permission-orders.index')->with('success', "权限单已提交，单号：{$order->order_no}");
        }

        return redirect()->route('permission-orders.edit', $order)->with('success', '草稿已保存');
    }

    public function edit(PermissionOrder $permissionOrder)
    {
        if (! $permissionOrder->isDraft()) {
            return redirect()->route('permission-orders.index')->with('error', '仅草稿可编辑');
        }

        return view('permission-orders.edit', [
            'order' => $permissionOrder,
            'parsed' => [
                'department' => old('department', $permissionOrder->department),
                'fill_date' => old('fill_date', $permissionOrder->fill_date),
                'items' => $permissionOrder->items ?? [],
            ],
        ]);
    }

    public function update(Request $request, PermissionOrder $permissionOrder, DocxPermissionExtractor $extractor)
    {
        if (! $permissionOrder->isDraft()) {
            return redirect()->route('permission-orders.index')->with('error', '仅草稿可编辑');
        }

        $action = $request->input('_action', 'save');
        $strict = $action === 'submit';

        $rules = PermissionOrder::rules($strict);
        $rules['source_doc'] = ['nullable', 'file', 'max:20480'];
        $rules['items_json'] = ['nullable', 'string'];
        if ($strict) {
            $rules['paper_submitted'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);
        $parsed = $permissionOrder->draft_data ?? [];

        if ($request->hasFile('source_doc')) {
            $uploadedFile = $request->file('source_doc');
            if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'docx') {
                return back()->withInput()->with('error', '仅支持 .docx 格式的 Word 文件');
            }

            $parsed = $extractor->extract($uploadedFile->getRealPath());

            if (empty($parsed)) {
                return back()->withInput()->with('error', '无法解析上传的 Word 表单，请确认文件是否包含标准表格模板');
            }

            if ($permissionOrder->source_doc_path && Storage::disk('public')->exists($permissionOrder->source_doc_path)) {
                Storage::disk('public')->delete($permissionOrder->source_doc_path);
            }

            $storedPath = $uploadedFile->storeAs('permission_orders/' . $permissionOrder->id, 'source.docx', 'public');
            $permissionOrder->source_doc_path = $storedPath;
            $permissionOrder->source_file_name = $uploadedFile->getClientOriginalName();
        }

        $items = json_decode($request->input('items_json', '[]'), true) ?? [];
        if (! empty($items)) {
            $parsed['items'] = $items;
        }

        $permissionOrder->fill($validated);
        $permissionOrder->items = $parsed['items'] ?? $permissionOrder->items;
        $permissionOrder->draft_data = $parsed;
        $permissionOrder->updated_by = Auth::id();

        if ($strict) {
            $permissionOrder->status = 'voided';
            $permissionOrder->order_no = PermissionOrder::generateOrderNo();
            $permissionOrder->voided_by = $validated['voided_by'] ?? $permissionOrder->voided_by;
            $permissionOrder->voided_at = $validated['voided_at'] ?? now();
            $permissionOrder->paper_submitted = $request->boolean('paper_submitted');
            $permissionOrder->paper_submitted_at = $permissionOrder->paper_submitted ? now() : $permissionOrder->paper_submitted_at;
        }

        $permissionOrder->save();

        if ($strict) {
            return redirect()->route('permission-orders.index')->with('success', "权限单已提交，单号：{$permissionOrder->order_no}");
        }

        return back()->with('success', '草稿已保存');
    }

    public function destroy(PermissionOrder $permissionOrder)
    {
        if (! $permissionOrder->isDraft()) {
            return redirect()->route('permission-orders.index')->with('error', '仅草稿可删除');
        }

        if ($permissionOrder->source_doc_path && Storage::disk('public')->exists($permissionOrder->source_doc_path)) {
            Storage::disk('public')->delete($permissionOrder->source_doc_path);
        }

        $permissionOrder->delete();

        return redirect()->route('permission-orders.index')->with('success', '草稿已删除');
    }

    public function void(Request $request, PermissionOrder $permissionOrder)
    {
        if (! $permissionOrder->isDraft()) {
            return redirect()->route('permission-orders.index')->with('error', '该单据已处理');
        }

        $validated = $request->validate(PermissionOrder::rules(true));
        $validated['paper_submitted'] = $request->boolean('paper_submitted');

        $permissionOrder->status = 'voided';
        $permissionOrder->order_no = PermissionOrder::generateOrderNo();
        $permissionOrder->voided_by = $validated['voided_by'];
        $permissionOrder->voided_at = $validated['voided_at'];
        $permissionOrder->paper_submitted = $validated['paper_submitted'];
        $permissionOrder->paper_submitted_at = $validated['paper_submitted'] ? now() : $permissionOrder->paper_submitted_at;
        $permissionOrder->updated_by = Auth::id();
        $permissionOrder->save();

        return redirect()->route('permission-orders.index')->with('success', "权限单已作废，单号：{$permissionOrder->order_no}");
    }

    public function parse(Request $request, DocxPermissionExtractor $extractor)
    {
        $request->validate([
            'source_doc' => ['required', 'file', 'max:20480'],
        ]);

        $uploadedFile = $request->file('source_doc');

        if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'docx') {
            return response()->json(['error' => '仅支持 .docx 格式的 Word 文件'], 422);
        }

        $parsed = $extractor->extract($uploadedFile->getRealPath());

        if (empty($parsed)) {
            return response()->json(['error' => '无法解析上传的 Word 表单，请确认文件是否包含标准表格模板'], 422);
        }

        return response()->json([
            'parsed' => $parsed,
            'source_file_name' => $uploadedFile->getClientOriginalName(),
        ]);
    }

    public function togglePaper(Request $request, PermissionOrder $permissionOrder)
    {
        $permissionOrder->paper_submitted = ! $permissionOrder->paper_submitted;
        $permissionOrder->paper_submitted_at = $permissionOrder->paper_submitted ? now() : null;
        $permissionOrder->updated_by = Auth::id();
        $permissionOrder->save();

        return response()->json([
            'paper_submitted' => $permissionOrder->paper_submitted,
            'paper_submitted_at' => optional($permissionOrder->paper_submitted_at)->format('Y-m-d H:i'),
            'label' => $permissionOrder->paperSubmittedLabel(),
        ]);
    }

    public function downloadDoc(PermissionOrder $permissionOrder)
    {
        if (! $permissionOrder->source_doc_path || ! Storage::disk('public')->exists($permissionOrder->source_doc_path)) {
            abort(404, '源文件不存在');
        }

        $absolutePath = Storage::disk('public')->path($permissionOrder->source_doc_path);
        $downloadName = $permissionOrder->source_file_name ?: '岗位权限调整单.docx';

        return response()->download($absolutePath, $downloadName);
    }
}
