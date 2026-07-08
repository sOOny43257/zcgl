<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableIntakeItem;
use App\Services\ConsumableStockService;
use Illuminate\Http\Request;

class ConsumableIntakeController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsumableIntakeOrder::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('order_no', 'like', "%{$s}%");
        }

        $orders = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('consumable-intakes.index', compact('orders'));
    }

    public function create()
    {
        $suppliers = \App\Models\DepartmentCode::type('supplier')->orderBy('code')->get();
        $consumables = Consumable::orderBy('name')->get();

        return view('consumable-intakes.create', compact('suppliers', 'consumables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'intake_date' => 'required|date',
            'supplier_code' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.consumable_id' => 'required|exists:consumables,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        $order = ConsumableIntakeOrder::create([
            'order_no' => ConsumableIntakeOrder::generateOrderNo(),
            'intake_date' => $validated['intake_date'],
            'supplier_code' => $validated['supplier_code'] ?? null,
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'draft',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $subtotal = isset($item['unit_price']) ? $item['unit_price'] * $item['quantity'] : null;
            ConsumableIntakeItem::create([
                'intake_order_id' => $order->id,
                'consumable_id' => $item['consumable_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? null,
                'subtotal' => $subtotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        return redirect()->route('consumable-intakes.show', $order)->with('success', '入库单创建成功');
    }

    public function show(ConsumableIntakeOrder $consumable_intake)
    {
        $order = $consumable_intake;
        $order->load('items.consumable');

        return view('consumable-intakes.show', compact('order'));
    }

    public function edit(ConsumableIntakeOrder $consumable_intake)
    {
        $order = $consumable_intake;

        if (!$order->isDraft()) {
            return redirect()->route('consumable-intakes.show', $order)
                ->with('error', '只有草稿状态的入库单才能编辑');
        }

        $order->load('items.consumable');
        $suppliers = \App\Models\DepartmentCode::type('supplier')->orderBy('code')->get();
        $consumables = Consumable::orderBy('name')->get();

        return view('consumable-intakes.edit', compact('order', 'suppliers', 'consumables'));
    }

    public function update(Request $request, ConsumableIntakeOrder $consumable_intake)
    {
        $order = $consumable_intake;

        if (!$order->isDraft()) {
            return redirect()->route('consumable-intakes.show', $order)
                ->with('error', '只有草稿状态的入库单才能编辑');
        }

        $validated = $request->validate([
            'intake_date' => 'required|date',
            'supplier_code' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.consumable_id' => 'required|exists:consumables,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:500',
        ]);

        $order->update([
            'intake_date' => $validated['intake_date'],
            'supplier_code' => $validated['supplier_code'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        // Replace items
        $order->items()->delete();
        foreach ($validated['items'] as $item) {
            $subtotal = isset($item['unit_price']) ? $item['unit_price'] * $item['quantity'] : null;
            ConsumableIntakeItem::create([
                'intake_order_id' => $order->id,
                'consumable_id' => $item['consumable_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'] ?? null,
                'subtotal' => $subtotal,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }

        return redirect()->route('consumable-intakes.show', $order)->with('success', '入库单更新成功');
    }

    public function destroy(ConsumableIntakeOrder $consumable_intake)
    {
        $order = $consumable_intake;

        if ($order->isCompleted()) {
            return redirect()->route('consumable-intakes.index')
                ->with('error', '已完成的入库单不能删除');
        }

        $order->delete();

        return redirect()->route('consumable-intakes.index')->with('success', '入库单已删除');
    }

    public function complete(ConsumableIntakeOrder $consumable_intake, ConsumableStockService $service)
    {
        try {
            $service->completeIntake($consumable_intake);
            return redirect()->route('consumable-intakes.show', $consumable_intake)
                ->with('success', '入库完成，库存已更新');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(ConsumableIntakeOrder $consumable_intake, ConsumableStockService $service)
    {
        $user = auth()->user();
        try {
            $service->cancelIntake($consumable_intake, $user->name, $user->id);
            return redirect()->route('consumable-intakes.show', $consumable_intake)
                ->with('success', '入库单已作废');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
