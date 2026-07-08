<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumableLog;
use Illuminate\Http\Request;

class ConsumableController extends Controller
{
    public function index(Request $request)
    {
        $query = Consumable::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('spec', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category_code')) {
            $query->where('category_code', $request->category_code);
        }

        if ($request->filled('low_stock') && $request->low_stock === '1') {
            $query->lowStock();
        }

        $consumables = $query->orderBy('name')->paginate(20)->withQueryString();
        $categories = \App\Models\DepartmentCode::type('hc_category')->orderBy('code')->get();

        return view('consumables.index', compact('consumables', 'categories'));
    }

    public function create()
    {
        $categories = \App\Models\DepartmentCode::type('hc_category')->orderBy('code')->get();
        $units = \App\Models\DepartmentCode::type('hc_unit')->orderBy('code')->get();
        $suppliers = \App\Models\DepartmentCode::type('supplier')->orderBy('code')->get();

        return view('consumables.create', compact('categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'category_code' => 'required|string|max:50',
            'spec' => 'nullable|string|max:200',
            'unit_code' => 'required|string|max:50',
            'supplier_code' => 'nullable|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;
        Consumable::create($validated);

        return redirect()->route('consumables.index')->with('success', '耗材添加成功');
    }

    public function show(Consumable $consumable)
    {
        $logs = ConsumableLog::where('consumable_id', $consumable->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('consumables.show', compact('consumable', 'logs'));
    }

    public function edit(Consumable $consumable)
    {
        $categories = \App\Models\DepartmentCode::type('hc_category')->orderBy('code')->get();
        $units = \App\Models\DepartmentCode::type('hc_unit')->orderBy('code')->get();
        $suppliers = \App\Models\DepartmentCode::type('supplier')->orderBy('code')->get();

        return view('consumables.edit', compact('consumable', 'categories', 'units', 'suppliers'));
    }

    public function update(Request $request, Consumable $consumable)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'category_code' => 'required|string|max:50',
            'spec' => 'nullable|string|max:200',
            'unit_code' => 'required|string|max:50',
            'supplier_code' => 'nullable|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
            'unit_price' => 'nullable|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $validated['min_stock'] = $validated['min_stock'] ?? 0;

        // Log changes
        $user = auth()->user();
        $trackedFields = [
            'name' => '耗材名称', 'category_code' => '分类', 'spec' => '规格型号',
            'unit_code' => '单位', 'min_stock' => '安全库存', 'unit_price' => '参考单价',
        ];

        foreach ($trackedFields as $field => $label) {
            if (isset($validated[$field]) && $validated[$field] != $consumable->$field) {
                ConsumableLog::create([
                    'consumable_id' => $consumable->id,
                    'consumable_name' => $consumable->name,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'action' => 'update',
                    'description' => "{$label}：{$consumable->$field} → {$validated[$field]}",
                    'created_at' => now(),
                ]);
            }
        }

        $consumable->update($validated);

        return redirect()->route('consumables.index')->with('success', '耗材更新成功');
    }

    public function destroy(Consumable $consumable)
    {
        $user = auth()->user();

        ConsumableLog::create([
            'consumable_id' => $consumable->id,
            'consumable_name' => $consumable->name,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'delete',
            'description' => "删除耗材：{$consumable->name}，删除时库存 {$consumable->current_stock}",
            'old_stock' => $consumable->current_stock,
            'created_at' => now(),
        ]);

        $consumable->delete();

        return redirect()->route('consumables.index')->with('success', '耗材已删除');
    }

    /**
     * JSON API for autocomplete/search
     */
    public function searchJson(Request $request)
    {
        $query = Consumable::query();

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qr) use ($q) {
                $qr->where('name', 'like', "%{$q}%")
                    ->orWhere('spec', 'like', "%{$q}%");
            });
        }

        return $query->select('id', 'name', 'spec', 'unit_code', 'current_stock', 'unit_price')
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'spec' => $c->spec,
                'unit_name' => $c->unitName(),
                'current_stock' => $c->current_stock,
                'unit_price' => $c->unit_price,
            ]);
    }
}
