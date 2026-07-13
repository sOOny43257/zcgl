<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Services\CsvImporter;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(Asset::query(), $request);
        $this->applySort($query, $request);

        $assets = $query->paginate(20)->withQueryString();
        $departments = Asset::select('department')->distinct()->whereNotNull('department')->pluck('department');

        return view('assets.index', compact('assets', 'departments'));
    }

    public function create()
    {
        return view('assets.create');
    }

    public function store(StoreAssetRequest $request)
    {
        Asset::create($request->validated());
        return redirect()->route('assets.index')->with('success', '资产添加成功');
    }

    public function show(Asset $asset)
    {
        $logs = $asset->logs()->with('user')->limit(50)->get();
        $repairs = $asset->repairs()->limit(20)->get();

        // 构建 log_id → 调拨单号 的映射
        $transferOrders = \App\Models\TransferOrder::where('asset_id', $asset->id)->get();
        $logToOrder = [];
        foreach ($transferOrders as $to) {
            foreach (($to->log_ids ?? []) as $lid) {
                $logToOrder[$lid] = $to->order_no;
            }
        }

        // 构建 order_no → transfer_order_id 的映射（用于变更历史展示调拨单链接）
        $relevantOrderNos = $logs->pluck('reference_no')->filter()->unique()
            ->merge(collect($logToOrder)->values())
            ->unique()->values()->all();
        $refOrderMap = [];
        if (!empty($relevantOrderNos)) {
            $refOrderMap = \App\Models\TransferOrder::whereIn('order_no', $relevantOrderNos)
                ->pluck('id', 'order_no')->all();
        }

        return view('assets.show', compact('asset', 'logs', 'logToOrder', 'refOrderMap', 'repairs'));
    }

    public function edit(Asset $asset)
    {
        return view('assets.edit', compact('asset'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $asset->update($request->validated());
        return redirect()->route('assets.index')->with('success', '资产更新成功');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('assets.index')->with('success', '资产删除成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        Asset::whereIn('id', $ids)->delete();

        return redirect()->route('assets.index')->with('success', '批量删除成功');
    }

    // JSON 列表（AJAX 分页 + 筛选）
    public function jsonIndex(Request $request)
    {
        $query = Asset::query();

        // 支持按 ID 列表查询（供调拨预选等场景）
        if ($request->has('ids')) {
            $ids = explode(',', $request->get('ids'));
            $query->whereIn('id', $ids);
        }

        $query = $this->applyFilters($query, $request);
        $this->applySort($query, $request);

        $perPage = min((int) $request->get("per_page", 20), 500);
        $assets = $query->paginate($perPage)->withQueryString();

        // 附加中文名
        $deptMap = \App\Models\DepartmentCode::type('department')->pluck('name', 'code');
        $catMap = \App\Models\DepartmentCode::type('category')->pluck('name', 'code');
        $statusMap = \App\Models\DepartmentCode::type('status')->pluck('name', 'code');
        $data = $assets->items();
        foreach ($data as $a) {
            $a->department_name = $deptMap[$a->department] ?? $a->department;
            $a->category_name = $catMap[$a->category] ?? $a->category;
            $a->status_name = $statusMap[$a->status] ?? $a->status;
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $assets->currentPage(),
                'last_page' => $assets->lastPage(),
                'per_page' => $assets->perPage(),
                'total' => $assets->total(),
            ],
        ]);
    }

    // JSON 搜索（供借用模块使用）
    public function searchJson(Request $request)
    {
        $q = $request->get('q', '');
        $assets = Asset::where(function ($query) use ($q) {
                $query->where('asset_code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('ip', 'like', "%{$q}%");
            })
            ->select('id', 'asset_code', 'name', 'ip', 'department', 'status', 'category', 'brand', 'model', 'room', 'user')
            ->limit(50)
            ->get();

        // 附加中文名
        $deptMap = \App\Models\DepartmentCode::type('department')->pluck('name', 'code');
        $catMap = \App\Models\DepartmentCode::type('category')->pluck('name', 'code');
        $statusMap = \App\Models\DepartmentCode::type('status')->pluck('name', 'code');
        foreach ($assets as $a) {
            $a->department_name = $deptMap[$a->department] ?? $a->department;
            $a->category_name = $catMap[$a->category] ?? $a->category;
            $a->status_name = $statusMap[$a->status] ?? $a->status;
        }

        return response()->json($assets);
    }

    // 资产盘点列表页
    public function check(Request $request)
    {
        $categories = \App\Models\DepartmentCode::type('category')->orderBy('code')->get();
        $departments = Asset::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');

        $deptMap = \App\Models\DepartmentCode::type('department')->pluck('name', 'code');

        // 预计算每个部门各类别资产数量
        $counts = [];
        $rows = Asset::selectRaw('department, category, count(*) as cnt')
            ->whereIn('department', $departments)
            ->groupBy('department', 'category')
            ->get();
        foreach ($rows as $row) {
            $counts[$row->department][$row->category] = $row->cnt;
        }

        return view('assets.check', compact('categories', 'departments', 'deptMap', 'counts'));
    }

    // 资产盘点打印内容（返回HTML片段）
    public function checkPrint(Request $request)
    {
        $request->validate([
            'departments' => 'required|array',
            'categories' => 'nullable|array',
        ]);

        $departments = $request->departments;
        $categories = $request->categories ?? [];

        $query = Asset::whereIn('department', $departments);
        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }
        $assets = $query->orderBy('department')->orderBy('room')->get();
        $grouped = $assets->groupBy('department');

        $deptMap = \App\Models\DepartmentCode::type('department')->pluck('name', 'code');
        $catMap = \App\Models\DepartmentCode::type('category')->pluck('name', 'code');

        $unit = '和平区税务局';

        return view('assets.check-print', compact('grouped', 'deptMap', 'catMap', 'unit', 'categories'));
    }

    // 盘点表导出（旧版保留）
    public function export(Request $request)
    {
        $request->validate(['department' => 'nullable|string']);

        $department = $request->department;
        if (! auth()->user()->isAdmin()) {
            $department = auth()->user()->department;
        }

        $query = Asset::query();
        if ($department) {
            $query->where('department', $department);
        }
        $assets = $query->orderBy('department')->orderBy('room')->get();
        $grouped = $assets->groupBy('department');

        return view('assets.export', compact('grouped'));
    }

    // CSV 导出预览页
    public function exportPreview(Request $request)
    {
        $query = $this->applyFilters(Asset::query(), $request);
        $this->applySort($query, $request);

        $total = $query->count();
        $assets = $query->paginate(30)->withQueryString();
        $departments = Asset::select('department')->distinct()->whereNotNull('department')->pluck('department');

        return view('assets.export-preview', compact('assets', 'departments', 'total'));
    }

    // CSV 导出
    public function exportCsv(Request $request)
    {
        $query = $this->applyFilters(Asset::query(), $request);
        $assets = $query->orderBy('id')->get();

        $filename = 'assets_export_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($assets) {
            $file = fopen('php://output', 'w');
            // BOM for Excel UTF-8 compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // 表头（与导入模板一致）
            fputcsv($file, ['序号', '自有编号', '财务编码', '资产名称', '部门', '房间号', 'IP地址', 'MAC地址', 'SN序列号', '品牌', '规格型号', '类别', '状态', '使用人', '备注']);

            foreach ($assets as $i => $a) {
                fputcsv($file, [
                    $i + 1,
                    $a->asset_code, $a->financial_code,
                    $a->name, Asset::translateDept($a->department), $a->room, $a->ip, $a->mac,
                    $a->sn, $a->brand, $a->model, Asset::translateCat($a->category), Asset::translateStatus($a->status),
                    $a->user, $a->remarks,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // CSV 模板下载
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="asset_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            // 中文表头
            fputcsv($file, ['序号', '自有编号', '财务编码', '资产名称', '部门', '房间号', 'IP地址', 'MAC地址', 'SN序列号', '品牌', '规格型号', '类别', '状态', '使用人', '备注']);
            fputcsv($file, ['1', '', '', '办公室台式机-01', '办公室', '301', '192.168.1.201', 'AA:BB:CC:DD:EE:01', 'SN20250001', '联想', '启天M428', '台式计算机（国产）', '在用', '张三', '']);
            fputcsv($file, ['2', '', '', '纳税服务科打印机-01', '纳税服务科', '401', '192.168.1.202', 'AA:BB:CC:DD:EE:02', 'SN20250002', 'HP', 'LaserJet Pro', '打印机', '在用', '李四', '']);
            fputcsv($file, ['3', '', '', '信息中心服务器-01', '信息中心', '501', '', '', 'SN20250003', '浪潮', 'NF5280M5', '服务器', '在用', '', '']);
            fputcsv($file, ['4', '', '', '闲置交换机-01', '信息中心', '502', '', '', '', '', '', '交换机', '闲置', '', '无IP和MAC的示例']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // CSV 导入页面
    public function importForm()
    {
        return view('assets.import');
    }

    // 数据字典验证（中文名→编码，不在字典中则报错）
    private function validateCodeField($value, $type): array
    {
        if (empty($value)) return ['code' => '', 'error' => null];
        $codes = \App\Models\DepartmentCode::type($type)->get();
        // 尝试精确匹配编码
        $byCode = $codes->firstWhere('code', $value);
        if ($byCode) return ['code' => $byCode->code, 'error' => null];
        // 尝试精确匹配中文名
        $byName = $codes->firstWhere('name', $value);
        if ($byName) return ['code' => $byName->code, 'error' => null];
        // 查找最接近的匹配作为建议
        $best = null; $bestSim = 0;
        foreach ($codes as $c) {
            similar_text($c->name, $value, $pct);
            if ($pct > $bestSim) { $bestSim = $pct; $best = $c; }
        }
        $hint = $best && $bestSim >= 50 ? "，建议: {$best->name}({$best->code})" : '';
        return ['code' => $value, 'error' => "「{$value}」不在数据字典中{$hint}"];
    }

    // CSV 解析预览（返回 JSON）
    public function parseCsv(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // 自动检测编码并转为 UTF-8（WPS 等编辑器可能改变编码）
        $content = file_get_contents($path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $path = sys_get_temp_dir() . '/utf8_' . uniqid() . '.csv';
            file_put_contents($path, $content);
        }

        $handle = fopen($path, 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return response()->json(['error' => 'CSV为空'], 422);
        }

        // 列名映射（支持中/英文列名，序号列会被忽略）
        $nameMap = [
            '序号' => null, // 序号列不导入
            '自有编号' => 'asset_code', 'asset_code' => 'asset_code',
            '财务编码' => 'financial_code', 'financial_code' => 'financial_code',
            '资产名称' => 'name', 'name' => 'name',
            '部门' => 'department', 'department' => 'department',
            '房间号' => 'room', 'room' => 'room',
            'ip地址' => 'ip', 'ip' => 'ip',
            'mac地址' => 'mac', 'mac' => 'mac',
            'sn序列号' => 'sn', 'sn' => 'sn',
            '品牌' => 'brand', 'brand' => 'brand',
            '规格型号' => 'model', 'model' => 'model',
            '类别' => 'category', 'category' => 'category',
            '状态' => 'status', 'status' => 'status',
            '使用人' => 'user', 'user' => 'user',
            '备注' => 'remarks', 'remarks' => 'remarks',
        ];

        $map = [];
        foreach ($headers as $i => $h) {
            $key = trim(strtolower($h));
            if (isset($nameMap[$key])) {
                $map[$nameMap[$key]] = $i;
            }
        }

        $columns = ['asset_code', 'financial_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];
        $rows = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = [];
            foreach ($columns as $col) {
                $idx = $map[$col] ?? -1;
                $data[$col] = $idx >= 0 ? trim($row[$idx] ?? '') : '';
            }

            $errors = [];
            $fieldErrors = [];
            if (!empty($data['ip'])) {
                if (!filter_var($data['ip'], FILTER_VALIDATE_IP)) { $errors[] = 'IP格式不合法'; $fieldErrors['ip'] = 'IP格式不合法'; }
            }
            if (!empty($data['mac'])) {
                if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data['mac'])) { $errors[] = 'MAC格式不合法'; $fieldErrors['mac'] = 'MAC格式不合法'; }
            }

            // 验证数据字典字段（接受中文名，转为编码）
            foreach (['department' => 'department', 'category' => 'category', 'status' => 'status'] as $field => $type) {
                if (!empty($data[$field])) {
                    $result = $this->validateCodeField($data[$field], $type);
                    if ($result['error']) {
                        $errors[] = $result['error'];
                        $fieldErrors[$field] = $result['error'];
                    } else {
                        $data[$field] = $result['code']; // 转换为编码
                    }
                }
            }

            // 跳过全空行
            $businessFields = ['asset_code','financial_code','name','department','room','ip','mac','sn','brand','model','category','status','user','remarks'];
            if (empty(array_filter(array_intersect_key($data, array_flip($businessFields))))) {
                continue;
            }

            $data['_row'] = $rowNum;
            $data['_valid'] = empty($errors);
            $data['_errors'] = $errors;
            $data['_fieldErrors'] = $fieldErrors ?? [];
            // 中文显示名
            $data['_display'] = [
                'department' => Asset::translateDept($data['department'] ?? ''),
                'category' => Asset::translateCat($data['category'] ?? ''),
                'status' => Asset::translateStatus($data['status'] ?? ''),
            ];

            $rows[] = $data;
        }
        fclose($handle);

        return response()->json([
            'columns' => $columns,
            'rows' => $rows,
            'total' => count($rows),
            'valid' => count(array_filter($rows, fn($r) => $r['_valid'])),
        ]);
    }

    // 批量导入（提交校验后的数据）
    public function batchImport(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1']);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        // 字段长度限制（与 StoreAssetRequest 一致）
        $lengthLimits = [
            'asset_code' => 20, 'financial_code' => 50, 'name' => 200,
            'department' => 100, 'room' => 50, 'ip' => 45, 'mac' => 17,
            'sn' => 200, 'brand' => 100, 'model' => 100, 'category' => 50,
            'status' => 20, 'user' => 100,
        ];

        \DB::transaction(function () use ($request, &$imported, &$skipped, &$errors, $lengthLimits) {
            foreach ($request->rows as $i => $row) {
                // 跳过全空行
                $businessFields = ['asset_code','financial_code','name','department','room','ip','mac','sn','brand','model','category','status','user','remarks'];
                if (empty(array_filter(array_intersect_key($row, array_flip($businessFields))))) {
                    $skipped++; continue;
                }

                // asset_code 唯一性校验
                if (!empty($row['asset_code']) && Asset::where('asset_code', $row['asset_code'])->exists()) {
                    $errors[] = "第" . ($i + 2) . "行: 自有编号 {$row['asset_code']} 已存在";
                    $skipped++; continue;
                }

                // 字段长度校验
                $lengthFailed = false;
                foreach ($lengthLimits as $field => $max) {
                    if (isset($row[$field]) && mb_strlen($row[$field]) > $max) {
                        $errors[] = "第" . ($i + 2) . "行: {$field} 超过 {$max} 字符限制";
                        $skipped++;
                        $lengthFailed = true;
                        break;
                    }
                }
                if ($lengthFailed) continue;

                // IP/MAC 允许为空，但非空时验证格式
                if (!empty($row['ip']) && !filter_var($row['ip'], FILTER_VALIDATE_IP)) {
                    $errors[] = "第" . ($i + 2) . "行: IP格式不合法"; $skipped++; continue;
                }
                if (!empty($row['mac']) && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $row['mac'])) {
                    $errors[] = "第" . ($i + 2) . "行: MAC格式不合法"; $skipped++; continue;
                }

                // 验证并转换数据字典字段
                foreach (['department' => 'department', 'category' => 'category', 'status' => 'status'] as $field => $type) {
                    if (!empty($row[$field])) {
                        $result = $this->validateCodeField($row[$field], $type);
                        if ($result['error']) {
                            $errors[] = "第" . ($i + 2) . "行: " . $result['error'];
                            $skipped++; continue 2;
                        }
                        $row[$field] = $result['code'];
                    }
                }

                Asset::create([
                    'asset_code' => $row['asset_code'] ?? '',
                    'financial_code' => $row['financial_code'] ?? '',
                    'name' => $row['name'] ?? '',
                    'department' => $row['department'] ?? '',
                    'room' => $row['room'] ?? '',
                    'ip' => empty($row['ip']) ? null : $row['ip'],
                    'mac' => empty($row['mac']) ? null : $row['mac'],
                    'sn' => $row['sn'] ?? '',
                    'brand' => $row['brand'] ?? '',
                    'model' => $row['model'] ?? '',
                    'category' => $row['category'] ?: '台式计算机（非国产）',
                    'status' => $row['status'] ?: '在用',
                    'user' => $row['user'] ?? '',
                    'remarks' => $row['remarks'] ?? '',
                ]);
                $imported++;
            }
        });

        // 记录导入日志
        \App\Models\ImportLog::create([
            'type' => 'import',
            'file_name' => $request->input('file_name', '手动上传'),
            'total_rows' => count($request->rows),
            'inserted' => $imported,
            'updated' => 0,
            'skipped' => $skipped,
            'changed_details' => null,
            'errors' => $errors,
            'transfer_order_id' => null,
            'operator_id' => auth()->id(),
            'operator_name' => auth()->user()->name,
            'operator' => trim((string) $request->input('operator', '')) ?: auth()->user()->name,
            'import_reason' => trim((string) $request->input('import_reason', '')),
        ]);
        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "导入完成：成功 {$imported} 条，跳过 {$skipped} 条",
        ]);
    }

    // CSV 导入处理
    public function import(Request $request, CsvImporter $importer)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $path = $request->file('csv_file')->getRealPath();

        // 自动检测编码并转为 UTF-8
        $content = file_get_contents($path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $path = sys_get_temp_dir() . '/utf8_' . uniqid() . '.csv';
            file_put_contents($path, $content);
        }

        $result = $importer->import(
            $path,
            ['ip', 'mac'],
            function ($data, $rowNum) {
                if (empty($data['ip']) || empty($data['mac'])) {
                    return false; // 跳过
                }
                Asset::create([
                    'name' => $data['name'] ?? '',
                    'department' => $data['department'] ?? '',
                    'room' => $data['room'] ?? '',
                    'ip' => $data['ip'],
                    'mac' => $data['mac'],
                    'sn' => $data['sn'] ?? '',
                    'brand' => $data['brand'] ?? '',
                    'model' => $data['model'] ?? '',
                    'category' => $data['category'] ?: '台式计算机（非国产）',
                    'status' => $data['status'] ?: '在用',
                    'user' => $data['user'] ?? '',
                    'remarks' => $data['remarks'] ?? '',
                ]);
                return true;
            }
        );

        return redirect()->route('assets.index')->with($result['success'] ? 'success' : 'error', $result['message']);

    }
    // ================================================================
    // 批量更新（新增/修改混合模式）
    // ================================================================

    // 批量更新 — CSV 解析预览（检测已有数据并计算差异）
    public function parseBatchUpdate(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // 自动检测编码并转为 UTF-8
        $content = file_get_contents($path);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'GBK', 'GB2312', 'ASCII'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            $path = sys_get_temp_dir() . '/utf8_' . uniqid() . '.csv';
            file_put_contents($path, $content);
        }

        $handle = fopen($path, 'r');
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($handle);

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return response()->json(['error' => 'CSV为空'], 422);
        }

        // 列名映射
        $nameMap = [
            '序号' => null,
            '自有编号' => 'asset_code', 'asset_code' => 'asset_code',
            '财务编码' => 'financial_code', 'financial_code' => 'financial_code',
            '资产名称' => 'name', 'name' => 'name',
            '部门' => 'department', 'department' => 'department',
            '房间号' => 'room', 'room' => 'room',
            'ip地址' => 'ip', 'ip' => 'ip',
            'mac地址' => 'mac', 'mac' => 'mac',
            'sn序列号' => 'sn', 'sn' => 'sn',
            '品牌' => 'brand', 'brand' => 'brand',
            '规格型号' => 'model', 'model' => 'model',
            '类别' => 'category', 'category' => 'category',
            '状态' => 'status', 'status' => 'status',
            '使用人' => 'user', 'user' => 'user',
            '备注' => 'remarks', 'remarks' => 'remarks',
        ];

        $map = [];
        foreach ($headers as $i => $h) {
            $key = trim(strtolower($h));
            if (isset($nameMap[$key])) {
                $map[$nameMap[$key]] = $i;
            }
        }

        $columns = ['asset_code', 'financial_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];

        // 预加载所有已有资产（按 asset_code 索引）
        $existingAssets = Asset::whereNotNull('asset_code')->get()->keyBy('asset_code');

        $rows = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $data = [];
            foreach ($columns as $col) {
                $idx = $map[$col] ?? -1;
                $data[$col] = $idx >= 0 ? trim($row[$idx] ?? '') : '';
            }

            // 跳过全空行
            $businessFields = ['asset_code','financial_code','name','department','room','ip','mac','sn','brand','model','category','status','user','remarks'];
            if (empty(array_filter(array_intersect_key($data, array_flip($businessFields))))) {
                continue;
            }

            $errors = [];
            $fieldErrors = [];

            // IP 格式校验
            if (!empty($data['ip'])) {
                if (!filter_var($data['ip'], FILTER_VALIDATE_IP)) {
                    $errors[] = 'IP格式不合法'; $fieldErrors['ip'] = 'IP格式不合法';
                }
            }
            if (!empty($data['mac'])) {
                if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $data['mac'])) {
                    $errors[] = 'MAC格式不合法'; $fieldErrors['mac'] = 'MAC格式不合法';
                }
            }

            // 验证数据字典字段
            foreach (['department' => 'department', 'category' => 'category', 'status' => 'status'] as $field => $type) {
                if (!empty($data[$field])) {
                    $result = $this->validateCodeField($data[$field], $type);
                    if ($result['error']) {
                        $errors[] = $result['error']; $fieldErrors[$field] = $result['error'];
                    } else {
                        $data[$field] = $result['code'];
                    }
                }
            }

            // 检测是否为已有资产
            $changes = [];
            $changeType = 'new';
            if (!empty($data['asset_code']) && isset($existingAssets[$data['asset_code']])) {
                $existing = $existingAssets[$data['asset_code']];
                $changeType = 'update';
                $compareFields = ['financial_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];
                foreach ($compareFields as $f) {
                    $oldVal = trim((string)($existing->$f ?? ''));
                    $newVal = trim((string)($data[$f] ?? ''));
                    if ($oldVal !== $newVal) {
                        $changes[$f] = ['old' => $oldVal, 'new' => $newVal];
                    }
                }
                if (empty($changes)) {
                    $changeType = 'no_change';
                }
            } elseif (!empty($data['asset_code'])) {
                $changeType = 'new';
            }

            // 显示名
            $display = [
                'department' => Asset::translateDept($data['department'] ?? ''),
                'category' => Asset::translateCat($data['category'] ?? ''),
                'status' => Asset::translateStatus($data['status'] ?? ''),
            ];

            $rows[] = [
                '_row' => $rowNum,
                '_valid' => empty($errors),
                '_errors' => $errors,
                '_fieldErrors' => $fieldErrors,
                '_changeType' => $changeType,
                '_changes' => $changes,
                '_display' => $display,
                'asset_code' => $data['asset_code'] ?? '',
                'financial_code' => $data['financial_code'] ?? '',
                'name' => $data['name'] ?? '',
                'department' => $data['department'] ?? '',
                'room' => $data['room'] ?? '',
                'ip' => $data['ip'] ?? '',
                'mac' => $data['mac'] ?? '',
                'sn' => $data['sn'] ?? '',
                'brand' => $data['brand'] ?? '',
                'model' => $data['model'] ?? '',
                'category' => $data['category'] ?? '',
                'status' => $data['status'] ?? '',
                'user' => $data['user'] ?? '',
                'remarks' => $data['remarks'] ?? '',
            ];
        }
        fclose($handle);

        return response()->json([
            'columns' => $columns,
            'rows' => $rows,
            'total' => count($rows),
            'newCount' => count(array_filter($rows, fn($r) => $r['_changeType'] === 'new')),
            'updateCount' => count(array_filter($rows, fn($r) => $r['_changeType'] === 'update')),
            'noChangeCount' => count(array_filter($rows, fn($r) => $r['_changeType'] === 'no_change')),
            'valid' => count(array_filter($rows, fn($r) => $r['_valid'])),
        ]);
    }

    // 提交批量更新（混合模式：新增 + 修改）
    public function submitBatchUpdate(Request $request)
    {
        $request->validate(['rows' => 'required|array|min:1']);

        // 批次起始时间，用于事后回写 AssetLog.reference_no
        $batchStart = now();
        $manualOperator = trim((string) $request->input('operator', ''));
        $importReason = trim((string) $request->input('import_reason', ''));

        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $changedDetails = [];
        $needsTransferOrder = false;
        $transferChangeAssets = [];
        $nonFinancialFields = ['name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];

        $lengthLimits = [
            'asset_code' => 20, 'financial_code' => 50, 'name' => 200,
            'department' => 100, 'room' => 50, 'ip' => 45, 'mac' => 17,
            'sn' => 200, 'brand' => 100, 'model' => 100, 'category' => 50,
            'status' => 20, 'user' => 100,
        ];

        // 预加载已有资产
        $assetCodes = [];
        foreach ($request->rows as $row) {
            if (!empty($row['asset_code'])) $assetCodes[] = $row['asset_code'];
        }
        $existingMap = Asset::whereIn('asset_code', $assetCodes)->get()->keyBy('asset_code');

        \DB::transaction(function () use ($request, &$inserted, &$updated, &$skipped, &$errors, &$changedDetails, &$needsTransferOrder, &$transferChangeAssets, $lengthLimits, $existingMap, $nonFinancialFields) {
            foreach ($request->rows as $i => $row) {
                $businessFields = ['asset_code','financial_code','name','department','room','ip','mac','sn','brand','model','category','status','user','remarks'];
                if (empty(array_filter(array_intersect_key($row, array_flip($businessFields))))) {
                    $skipped++; continue;
                }

                // 字段长度校验
                $lengthFailed = false;
                foreach ($lengthLimits as $field => $max) {
                    if (isset($row[$field]) && mb_strlen($row[$field]) > $max) {
                        $errors[] = '第' . ($i + 2) . "行: {$field} 超过 {$max} 字符限制";
                        $skipped++; $lengthFailed = true; break;
                    }
                }
                if ($lengthFailed) continue;

                if (!empty($row['ip']) && !filter_var($row['ip'], FILTER_VALIDATE_IP)) {
                    $errors[] = '第' . ($i + 2) . '行: IP格式不合法'; $skipped++; continue;
                }
                if (!empty($row['mac']) && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $row['mac'])) {
                    $errors[] = '第' . ($i + 2) . '行: MAC格式不合法'; $skipped++; continue;
                }

                // 验证数据字典字段
                foreach (['department' => 'department', 'category' => 'category', 'status' => 'status'] as $field => $type) {
                    if (!empty($row[$field])) {
                        $result = $this->validateCodeField($row[$field], $type);
                        if ($result['error']) {
                            $errors[] = '第' . ($i + 2) . '行: ' . $result['error'];
                            $skipped++; continue 2;
                        }
                        $row[$field] = $result['code'];
                    }
                }

                $assetCode = $row['asset_code'] ?? '';

                if (!empty($assetCode) && isset($existingMap[$assetCode])) {
                    // === 更新已有资产 ===
                    $asset = $existingMap[$assetCode];
                    $rowChanges = [];
                    $hasFinancialOnly = true;
                    $compareFields = ['financial_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'remarks'];
                    foreach ($compareFields as $f) {
                        $oldVal = trim((string)($asset->$f ?? ''));
                        $newVal = trim((string)($row[$f] ?? ''));
                        if ($oldVal !== $newVal) {
                            $rowChanges[$f] = ['old' => $oldVal, 'new' => $newVal];
                            if (in_array($f, $nonFinancialFields)) $hasFinancialOnly = false;
                        }
                    }
                    if (empty($rowChanges)) { $skipped++; continue; }

                    $updateData = [];
                    foreach ($compareFields as $f) {
                        if (array_key_exists($f, $rowChanges)) $updateData[$f] = $row[$f];
                    }
                    $asset->update($updateData);
                    $updated++;

                    $changedDetails[] = [
                        'asset_code' => $assetCode,
                        'asset_name' => $row['name'] ?: $asset->name,
                        'type' => 'update',
                        'changes' => $rowChanges,
                    ];

                    // 所有有变更的资产都纳入调拨单数据（含纯财务编码变更），
                    // 使打印条数与变更明细一致；仅当存在非财务字段变更时才生成调拨单
                    $transferChangeAssets[] = [
                        'asset_id' => $asset->id,
                        'asset_code' => $assetCode,
                        'changes' => $rowChanges,
                        'original' => $asset->getOriginal(),
                    ];
                    if (!$hasFinancialOnly) {
                        $needsTransferOrder = true;
                    }
                } else {
                    // === 新增资产 ===
                    if (!empty($assetCode) && Asset::where('asset_code', $assetCode)->exists()) {
                        $errors[] = '第' . ($i + 2) . "行: 自有编号 {$assetCode} 已存在";
                        $skipped++; continue;
                    }
                    $asset = Asset::create([
                        'asset_code' => $row['asset_code'] ?? '',
                        'financial_code' => $row['financial_code'] ?? '',
                        'name' => $row['name'] ?? '',
                        'department' => $row['department'] ?? '',
                        'room' => $row['room'] ?? '',
                        'ip' => empty($row['ip']) ? null : $row['ip'],
                        'mac' => empty($row['mac']) ? null : $row['mac'],
                        'sn' => $row['sn'] ?? '',
                        'brand' => $row['brand'] ?? '',
                        'model' => $row['model'] ?? '',
                        'category' => $row['category'] ?: '台式计算机（非国产）',
                        'status' => $row['status'] ?: '在用',
                        'user' => $row['user'] ?? '',
                        'remarks' => $row['remarks'] ?? '',
                    ]);
                    $inserted++;
                    $changedDetails[] = [
                        'asset_code' => $asset->asset_code,
                        'asset_name' => $row['name'] ?? '未命名',
                        'type' => 'insert',
                    ];
                }
            }
        });

        // 生成调拨单（仅当有非财务编码变更时）
        $transferOrderId = null;
        $orderNo = null;
        if ($needsTransferOrder && !empty($transferChangeAssets)) {
            $assetIds = array_column($transferChangeAssets, 'asset_id');
            $changesForOrder = [];
            $originalsForOrder = [];
            foreach ($transferChangeAssets as $tca) {
                $cid = (string)$tca['asset_id'];
                $changesForOrder[$cid] = collect($tca['changes'])->map(fn($c) => $c['new'])->toArray();
                $originalsForOrder[$cid] = collect($tca['changes'])->map(fn($c) => $c['old'])->toArray();
            }
            $orderNo = $this->generateOrderNo();
            $transferOrder = \App\Models\TransferOrder::create([
                'order_no' => $orderNo,
                'asset_id' => $assetIds[0],
                'log_ids' => [],
                'from_dept' => '',
                'to_dept' => '',
                'operator' => auth()->user()->name,
                'status' => 'active',
                'reason' => 'CSV批量更新：涉及' . count($transferChangeAssets) . '条资产变更',
                'draft_data' => [
                    'asset_ids' => $assetIds,
                    'original' => $originalsForOrder,
                    'changes' => $changesForOrder,
                ],
            ]);
            $transferOrderId = $transferOrder->id;
        }

        // 回写 AssetLog.reference_no，使资产变更历史能看到调拨单号
        if ($orderNo) {
            \App\Models\AssetLog::whereIn('asset_id', $assetIds)
                ->where('created_at', '>=', $batchStart)
                ->update(['reference_no' => $orderNo]);
        }

        // 记录导入日志
        $logType = 'mixed';
        if ($inserted > 0 && $updated === 0) $logType = 'import';
        elseif ($inserted === 0 && $updated > 0) $logType = 'update';

        \App\Models\ImportLog::create([
            'type' => $logType,
            'file_name' => $request->input('file_name', '手动上传'),
            'total_rows' => count($request->rows),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'changed_details' => $changedDetails,
            'errors' => $errors,
            'transfer_order_id' => $transferOrderId,
            'operator_id' => auth()->id(),
            'operator_name' => auth()->user()->name,
            'operator' => $manualOperator ?: auth()->user()->name,
            'import_reason' => $importReason,
        ]);

        $parts = [];
        if ($inserted > 0) $parts[] = "新增 {$inserted} 条";
        if ($updated > 0) $parts[] = "更新 {$updated} 条";
        if ($skipped > 0) $parts[] = "跳过 {$skipped} 条";
        $message = '操作完成：' . implode('，', $parts);
        if ($transferOrderId) $message .= '，已生成调拨单 ' . ($orderNo ?? '');

        return response()->json([
            'success' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'changed_details' => $changedDetails,
            'transfer_order_id' => $transferOrderId,
            'message' => $message,
        ]);
    }

    // 生成调拨单号
    private function generateOrderNo(): string
    {
        $prefix = 'DB' . date('Ymd');
        $last = \App\Models\TransferOrder::where('order_no', 'like', $prefix . '%')
            ->orderByDesc('order_no')->first();
        $num = $last ? ((int) substr($last->order_no, -3)) + 1 : 1;
        return $prefix . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    // 导入操作日志列表
    public function importLogs(Request $request)
    {
        $query = \App\Models\ImportLog::query();

        // 按日期筛选
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 按单号/文件名/操作人搜索
        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('file_name', 'like', "%{$search}%")
                  ->orWhere('operator_name', 'like', "%{$search}%")
                  ->orWhere('operator', 'like', "%{$search}%")
                  ->orWhere('import_reason', 'like', "%{$search}%")
                  ->orWhereHas('transferOrder', function ($tq) use ($search) {
                      $tq->where('order_no', 'like', "%{$search}%");
                  });
            });
        }

        // 默认隐藏已作废的（可传 show_cancelled=1 查看）
        if (!$request->boolean('show_cancelled')) {
            $query->where('is_cancelled', false);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        return view('assets.import-logs', compact('logs'));
    }

    // 作废导入日志（同步作废关联调拨单）
    public function voidImportLog(Request $request, \App\Models\ImportLog $importLog)
    {
        if ($importLog->is_cancelled) {
            return back()->with('error', '该导入日志已作废');
        }

        \DB::transaction(function () use ($importLog, $request) {
            $importLog->update([
                'is_cancelled' => true,
                'cancelled_at' => now(),
            ]);

            // 同步作废关联调拨单
            if ($importLog->transfer_order_id && $importLog->transferOrder) {
                $importLog->transferOrder->update([
                    'is_cancelled' => true,
                    'cancelled_at' => now(),
                ]);
            }

            // 清除 AssetLog 上回写的单号引用
            if ($importLog->transferOrder) {
                \App\Models\AssetLog::where('reference_no', $importLog->transferOrder->order_no)->update(['reference_no' => null]);
            }
        });

        return back()->with('success', '导入日志已作废' . ($importLog->transfer_order_id ? '，关联调拨单已同步作废' : ''));
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhere('mac', 'like', "%{$search}%")
                    ->orWhere('sn', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('room', 'like', "%{$search}%")
                    ->orWhere('user', 'like', "%{$search}%")
                    ->orWhere('asset_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('departments')) {
            $query->whereIn('department', (array) $request->departments);
        }
        if ($request->filled('statuses')) {
            $query->whereIn('status', (array) $request->statuses);
        }
        if ($request->filled('categories')) {
            $query->whereIn('category', (array) $request->categories);
        }
        if ($request->filled('brands')) {
            $query->whereIn('brand', (array) $request->brands);
        }
        if ($request->filled('rooms')) {
            $query->whereIn('room', (array) $request->rooms);
        }
        if ($request->filled('users')) {
            $query->whereIn('user', (array) $request->users);
        }
        if ($request->filled('models')) {
            $query->whereIn('model', (array) $request->models);
        }

        return $query;
    }

    private function applySort($query, Request $request)
    {
        $field = $request->get('sort', 'id');
        $dir = $request->get('direction', 'desc');
        $allowed = ['id', 'asset_code', 'name', 'department', 'room', 'ip', 'mac', 'sn', 'brand', 'model', 'category', 'status', 'user', 'created_at', 'updated_at'];

        if (in_array($field, $allowed)) {
            $query->orderBy($field, $dir === 'asc' ? 'asc' : 'desc');
        }
    }
}
