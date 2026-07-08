<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Services\ConsumableStockService;
use Illuminate\Http\Request;

class ConsumableUsageController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsumableUsage::with('consumable');

        if ($request->filled('consumable_id')) {
            $query->where('consumable_id', $request->consumable_id);
        }

        if ($request->filled('department_code')) {
            $query->where('department_code', $request->department_code);
        }

        if ($request->filled('date_from')) {
            $query->where('usage_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('usage_date', '<=', $request->date_to);
        }

        $usages = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $departments = \App\Models\DepartmentCode::type('department')->orderBy('code')->get();
        $consumables = Consumable::orderBy('name')->get();

        return view('consumable-usages.index', compact('usages', 'departments', 'consumables'));
    }

    public function create()
    {
        $departments = \App\Models\DepartmentCode::type('department')->orderBy('code')->get();
        $consumables = Consumable::orderBy('name')->get();

        return view('consumable-usages.create', compact('departments', 'consumables'));
    }

    public function store(Request $request, ConsumableStockService $service)
    {
        $validated = $request->validate([
            'department_code' => 'required|string|max:50',
            'usage_date' => 'required|date',
            'reason' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.consumable_id' => 'required|exists:consumables,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = auth()->user();

        try {
            $service->createBatchUsage(
                $validated['items'],
                $validated['department_code'],
                $validated['usage_date'],
                $validated['reason'],
                $user->name,
                $user->id
            );
            return redirect()->route('consumable-usages.index')->with('success', '领用成功，库存已扣减');
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(ConsumableUsage $consumable_usage)
    {
        $usage = $consumable_usage;
        $usage->load('consumable');

        return view('consumable-usages.show', compact('usage'));
    }
}
