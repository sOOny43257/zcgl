<?php

namespace App\Http\Controllers;

use App\Models\DepartmentCode;
use App\Services\CsvImporter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentCodeController extends Controller
{
    const TYPES = ['department' => '部门编码', 'category' => '类别编码', 'status' => '状态编码'];

    public function index(Request $request)
    {
        $query = DepartmentCode::query();
        $currentType = $request->get('type', 'department');

        $query->where('type', $currentType);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $codes = $query->orderBy('code')->paginate(30)->withQueryString();

        return view('codes.index', compact('codes', 'currentType'));
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'department');
        $typeLabel = self::TYPES[$type] ?? '编码';
        return view('codes.create', compact('type', 'typeLabel'));
    }

    public function store(Request $request)
    {
        $rules = [
            'type' => 'required|in:department,category,status',
            'name' => 'required|string|max:100',
        ];

        // code唯一性按type分组
        $rules['code'] = [
            'required', 'string', 'max:50',
            Rule::unique('department_codes', 'code')->where('type', $request->type),
        ];

        $request->validate($rules);

        DepartmentCode::create($request->only(['type', 'code', 'name']));

        return redirect()->route('codes.index', ['type' => $request->type])->with('success', '编码添加成功');
    }

    public function edit(DepartmentCode $code)
    {
        $typeLabel = self::TYPES[$code->type] ?? '编码';
        return view('codes.edit', compact('code', 'typeLabel'));
    }

    public function update(Request $request, DepartmentCode $code)
    {
        $validated = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('department_codes', 'code')->where('type', $code->type)->ignore($code->id),
            ],
            'name' => 'required|string|max:100',
        ]);

        $oldCode = $code->code;
        $code->update($validated);

        // 编码变更时同步更新关联数据
        if ($oldCode !== $code->code && $code->type === 'department') {
            $count = \App\Models\Asset::where('department', $oldCode)->update(['department' => $code->code]);
            return redirect()->route('codes.index', ['type' => $code->type])
                ->with('success', "编码更新成功，已同步更新 {$count} 条资产数据");
        }

        return redirect()->route('codes.index', ['type' => $code->type])->with('success', '编码更新成功');
    }

    public function destroy(DepartmentCode $code)
    {
        $type = $code->type;

        // 删除前检查关联性
        if ($type === 'department') {
            $count = \App\Models\Asset::where('department', $code->code)->count();
            if ($count > 0) {
                return back()->with('error', "无法删除：该部门编码正被 {$count} 条资产使用中");
            }
        }

        $code->delete();
        return redirect()->route('codes.index', ['type' => $type])->with('success', '编码删除成功');
    }

    public function importForm(Request $request)
    {
        $type = $request->get('type', 'department');
        $typeLabel = self::TYPES[$type] ?? '编码';
        return view('codes.import', compact('type', 'typeLabel'));
    }

    public function import(Request $request, CsvImporter $importer)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'type' => 'required|in:department,category,status',
        ]);

        $type = $request->type;

        $result = $importer->import(
            $request->file('csv_file')->getRealPath(),
            ['code', 'name'],
            function ($data) use ($type) {
                if (empty($data['code']) || empty($data['name'])) {
                    return false;
                }
                DepartmentCode::updateOrCreate(
                    ['type' => $type, 'code' => $data['code']],
                    ['name' => $data['name']]
                );
                return true;
            }
        );

        return redirect()->route('codes.index', ['type' => $type])
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
