<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use Illuminate\Http\Request;

class PrintTemplateController extends Controller
{
    public function index()
    {
        $templates = PrintTemplate::orderBy('module')->get();

        return view('print-templates.index', compact('templates'));
    }

    public function edit(PrintTemplate $printTemplate)
    {
        $pageMetaOptions = [
            '入库日期', '供应商', '采购单号', '总金额', '经办人', '验收人', '创建时间', '备注', '入库说明',
        ];

        $columnOptions = [
            'asset_code' => '资产编号',
            'financial_code' => '财务编号',
            'name' => '资产名称',
            'category' => '类别',
            'brand' => '品牌',
            'model' => '规格型号',
            'sn' => 'SN序列号',
            'department' => '部门',
            'room' => '房间号',
            'user' => '使用人',
            'purchase_price' => '单价',
            'remarks' => '备注',
        ];

        return view('print-templates.edit', compact('printTemplate', 'pageMetaOptions', 'columnOptions'));
    }

    public function update(Request $request, PrintTemplate $printTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'orientation' => 'required|string|in:' . implode(',', PrintTemplate::ORIENTATIONS),
            'is_active' => 'nullable|boolean',
            'config.page.title' => 'required|string|max:50',
            'config.page.order_no_prefix' => 'nullable|string|max:20',
            'config.page.meta' => 'nullable|array',
            'config.page.meta.*' => 'string|max:50',
            'config.table.show_index' => 'nullable|boolean',
            'config.table.show_total' => 'nullable|boolean',
            'config.table.columns' => 'nullable|array',
            'config.table.columns.*.key' => 'required_with:config.table.columns|string|max:50',
            'config.table.columns.*.label' => 'required_with:config.table.columns|string|max:50',
            'config.signatures' => 'nullable|array',
            'config.signatures.*' => 'string|max:50',
        ]);

        $printTemplate->update([
            'name' => $validated['name'],
            'orientation' => $validated['orientation'],
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $request->user()->id,
            'config' => $validated['config'] ?? $printTemplate->config,
        ]);

        return back()->with('success', '打印模板已保存');
    }

    public function resetToDefault(Request $request, PrintTemplate $printTemplate)
    {
        $printTemplate->update([
            'config' => PrintTemplate::defaultConfig($printTemplate->module),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', '已恢复默认配置');
    }
}
