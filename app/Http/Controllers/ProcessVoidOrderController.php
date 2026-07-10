<?php

namespace App\Http\Controllers;

use App\Models\ProcessVoidOrder;
use App\Services\DocxProcessVoidExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProcessVoidOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ProcessVoidOrder::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('tax_no', 'like', "%{$search}%")
                    ->orWhere('process_name', 'like', "%{$search}%")
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

        return view('process-void-orders.index', compact('orders'));
    }

    public function create()
    {
        return view('process-void-orders.create', [
            'order' => null,
            'parsed' => old('department') ? [
                'department' => old('department'),
                'flow_start_time' => old('flow_start_time'),
                'company_name' => old('company_name'),
                'tax_no' => old('tax_no'),
                'process_name' => old('process_name'),
                'termination_reason' => old('termination_reason'),
                'submitter_sign' => old('submitter_sign'),
            ] : null,
        ]);
    }

    public function store(Request $request, DocxProcessVoidExtractor $extractor)
    {
        $action = $request->input('_action', 'draft');
        $strict = $action === 'submit';

        $rules = [
            'source_doc' => ['required', 'file', 'max:20480'],
            'department' => ['nullable', 'string', 'max:100'],
            'flow_start_time' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'tax_no' => ['nullable', 'string', 'max:50'],
            'process_name' => ['nullable', 'string', 'max:2000'],
            'termination_reason' => ['nullable', 'string', 'max:2000'],
            'submitter_sign' => ['nullable', 'string', 'max:200'],
        ];

        if ($strict) {
            $rules['department'] = ['required', 'string', 'max:100'];
            $rules['company_name'] = ['required', 'string', 'max:200'];
            $rules['tax_no'] = ['required', 'string', 'max:50'];
            $rules['process_name'] = ['required', 'string', 'max:2000'];
            $rules['termination_reason'] = ['required', 'string', 'max:2000'];
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

        $order = ProcessVoidOrder::create([
            'status' => $strict ? 'voided' : 'draft',
            'order_no' => $strict ? ProcessVoidOrder::generateOrderNo() : null,
            'source_doc_path' => null,
            'source_file_name' => $uploadedFile->getClientOriginalName(),
            'department' => $validated['department'] ?? $parsed['department'] ?? '',
            'flow_start_time' => $validated['flow_start_time'] ?? $parsed['flow_start_time'] ?? '',
            'company_name' => $validated['company_name'] ?? $parsed['company_name'] ?? '',
            'tax_no' => $validated['tax_no'] ?? $parsed['tax_no'] ?? '',
            'process_name' => $validated['process_name'] ?? $parsed['process_name'] ?? '',
            'termination_reason' => $validated['termination_reason'] ?? $parsed['termination_reason'] ?? '',
            'submitter_sign' => $validated['submitter_sign'] ?? $parsed['submitter_sign'] ?? '',
            'voided_by' => $strict ? ($validated['voided_by'] ?? '') : null,
            'voided_at' => $strict ? ($validated['voided_at'] ?? now()) : null,
            'paper_submitted' => $strict ? $request->boolean('paper_submitted') : false,
            'paper_submitted_at' => $strict && $request->boolean('paper_submitted') ? now() : null,
            'draft_data' => $parsed,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $storedPath = $uploadedFile->storeAs('process_void_orders/' . $order->id, 'source.docx', 'public');
        $order->update(['source_doc_path' => $storedPath]);

        if ($strict) {
            return redirect()->route('process-void-orders.index')->with('success', "流程单已提交，单号：{$order->order_no}");
        }

        return redirect()->route('process-void-orders.edit', $order)->with('success', '草稿已保存');
    }

    public function edit(ProcessVoidOrder $processVoidOrder)
    {
        if (! $processVoidOrder->isDraft()) {
            return redirect()->route('process-void-orders.index')->with('error', '仅草稿可编辑');
        }

        return view('process-void-orders.edit', [
            'order' => $processVoidOrder,
            'parsed' => [
                'department' => old('department', $processVoidOrder->department),
                'flow_start_time' => old('flow_start_time', $processVoidOrder->flow_start_time),
                'company_name' => old('company_name', $processVoidOrder->company_name),
                'tax_no' => old('tax_no', $processVoidOrder->tax_no),
                'process_name' => old('process_name', $processVoidOrder->process_name),
                'termination_reason' => old('termination_reason', $processVoidOrder->termination_reason),
                'submitter_sign' => old('submitter_sign', $processVoidOrder->submitter_sign),
            ],
        ]);
    }

    public function update(Request $request, ProcessVoidOrder $processVoidOrder, DocxProcessVoidExtractor $extractor)
    {
        if (! $processVoidOrder->isDraft()) {
            return redirect()->route('process-void-orders.index')->with('error', '仅草稿可编辑');
        }

        $action = $request->input('_action', 'save');
        $strict = $action === 'submit';

        $rules = ProcessVoidOrder::rules($strict);
        $rules['source_doc'] = ['nullable', 'file', 'max:20480'];
        if ($strict) {
            $rules['paper_submitted'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);
        $parsed = $processVoidOrder->draft_data ?? [];

        if ($request->hasFile('source_doc')) {
        $uploadedFile = $request->file('source_doc');
        if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'docx') {
            return back()->withInput()->with('error', '仅支持 .docx 格式的 Word 文件');
        }
            $parsed = $extractor->extract($uploadedFile->getRealPath());

            if (empty($parsed)) {
                return back()->withInput()->with('error', '无法解析上传的 Word 表单，请确认文件是否包含标准表格模板');
            }

            if ($processVoidOrder->source_doc_path && Storage::disk('public')->exists($processVoidOrder->source_doc_path)) {
                Storage::disk('public')->delete($processVoidOrder->source_doc_path);
            }

            $storedPath = $uploadedFile->storeAs('process_void_orders/' . $processVoidOrder->id, 'source.docx', 'public');
            $processVoidOrder->source_doc_path = $storedPath;
            $processVoidOrder->source_file_name = $uploadedFile->getClientOriginalName();
        }

        $processVoidOrder->fill($validated);
        $processVoidOrder->draft_data = $parsed;
        $processVoidOrder->updated_by = Auth::id();

        if ($strict) {
            $processVoidOrder->status = 'voided';
            $processVoidOrder->order_no = ProcessVoidOrder::generateOrderNo();
            $processVoidOrder->voided_by = $validated['voided_by'] ?? $processVoidOrder->voided_by;
            $processVoidOrder->voided_at = $validated['voided_at'] ?? now();
            $processVoidOrder->paper_submitted = $request->boolean('paper_submitted');
            $processVoidOrder->paper_submitted_at = $processVoidOrder->paper_submitted ? now() : $processVoidOrder->paper_submitted_at;
        }

        $processVoidOrder->save();

        if ($strict) {
            return redirect()->route('process-void-orders.index')->with('success', "流程单已提交，单号：{$processVoidOrder->order_no}");
        }

        return back()->with('success', '草稿已保存');
    }

    public function destroy(ProcessVoidOrder $processVoidOrder)
    {
        if (! $processVoidOrder->isDraft()) {
            return redirect()->route('process-void-orders.index')->with('error', '仅草稿可删除');
        }

        if ($processVoidOrder->source_doc_path && Storage::disk('public')->exists($processVoidOrder->source_doc_path)) {
            Storage::disk('public')->delete($processVoidOrder->source_doc_path);
        }

        $processVoidOrder->delete();

        return redirect()->route('process-void-orders.index')->with('success', '草稿已删除');
    }

    public function void(Request $request, ProcessVoidOrder $processVoidOrder)
    {
        if (! $processVoidOrder->isDraft()) {
            return redirect()->route('process-void-orders.index')->with('error', '该单据已处理');
        }

        $validated = $request->validate(ProcessVoidOrder::rules(true));
        $validated['paper_submitted'] = $request->boolean('paper_submitted');

        $processVoidOrder->status = 'voided';
        $processVoidOrder->order_no = ProcessVoidOrder::generateOrderNo();
        $processVoidOrder->voided_by = $validated['voided_by'];
        $processVoidOrder->voided_at = $validated['voided_at'];
        $processVoidOrder->paper_submitted = $validated['paper_submitted'];
        $processVoidOrder->paper_submitted_at = $validated['paper_submitted'] ? now() : $processVoidOrder->paper_submitted_at;
        $processVoidOrder->updated_by = Auth::id();
        $processVoidOrder->save();

        return redirect()->route('process-void-orders.index')->with('success', "流程单已作废，单号：{$processVoidOrder->order_no}");
    }

    public function togglePaper(Request $request, ProcessVoidOrder $processVoidOrder)
    {
        $processVoidOrder->paper_submitted = ! $processVoidOrder->paper_submitted;
        $processVoidOrder->paper_submitted_at = $processVoidOrder->paper_submitted ? now() : null;
        $processVoidOrder->updated_by = Auth::id();
        $processVoidOrder->save();

        return response()->json([
            'paper_submitted' => $processVoidOrder->paper_submitted,
            'paper_submitted_at' => optional($processVoidOrder->paper_submitted_at)->format('Y-m-d H:i'),
            'label' => $processVoidOrder->paperSubmittedLabel(),
        ]);
    }

    public function downloadDoc(ProcessVoidOrder $processVoidOrder)
    {
        if (! $processVoidOrder->source_doc_path || ! Storage::disk('public')->exists($processVoidOrder->source_doc_path)) {
            abort(404, '源文件不存在');
        }

        $absolutePath = Storage::disk('public')->path($processVoidOrder->source_doc_path);
        $downloadName = $processVoidOrder->source_file_name ?: '流程作废申请单.docx';

        return response()->download($absolutePath, $downloadName);
    }
}
