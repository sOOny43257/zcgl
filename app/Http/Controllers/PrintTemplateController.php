<?php

namespace App\Http\Controllers;

use App\Models\PrintTemplate;
use App\Services\PrintService;
use Illuminate\Http\Request;

class PrintTemplateController extends Controller
{
    public function index()
    {
        $templates = PrintTemplate::orderBy('module')->get();
        $moduleLabels = collect(PrintService::moduleMeta())->map(fn($m) => $m['label'])->toArray();

        return view('print-templates.index', compact('templates', 'moduleLabels'));
    }

    public function edit(PrintTemplate $printTemplate)
    {
        $module = $printTemplate->module;
        $pageMetaOptions = PrintService::pageMetaOptions($module);
        $columnOptions = PrintService::columnOptions($module);

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
            'name' => PrintService::moduleLabel($printTemplate->module) . '打印模板',
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', '已恢复默认配置');
    }
}
