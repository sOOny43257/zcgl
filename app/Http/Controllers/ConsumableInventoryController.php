<?php

namespace App\Http\Controllers;

use App\Models\Consumable;
use App\Models\ConsumableInventory;
use App\Models\ConsumableInventoryItem;
use App\Services\ConsumableStockService;
use Illuminate\Http\Request;

class ConsumableInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsumableInventory::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $inventories = $query->orderByDesc('id')->paginate(20)->withQueryString();

        return view('consumable-inventories.index', compact('inventories'));
    }

    public function create()
    {
        $consumables = Consumable::orderBy('name')->get();

        return view('consumable-inventories.create', compact('consumables'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_date' => 'required|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.consumable_id' => 'required|exists:consumables,id',
            'items.*.actual_quantity' => 'required|integer|min:0',
            'items.*.reason' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        $inventory = ConsumableInventory::create([
            'inventory_no' => ConsumableInventory::generateInventoryNo(),
            'inventory_date' => $validated['inventory_date'],
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'draft',
            'remarks' => $validated['remarks'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $consumable = Consumable::find($item['consumable_id']);
            $bookQty = $consumable->current_stock;
            $actualQty = $item['actual_quantity'];

            ConsumableInventoryItem::create([
                'inventory_id' => $inventory->id,
                'consumable_id' => $item['consumable_id'],
                'book_quantity' => $bookQty,
                'actual_quantity' => $actualQty,
                'difference' => $actualQty - $bookQty,
                'reason' => $item['reason'] ?? null,
            ]);
        }

        return redirect()->route('consumable-inventories.show', $inventory)
            ->with('success', '盘点单创建成功');
    }

    public function show(ConsumableInventory $consumable_inventory)
    {
        $inventory = $consumable_inventory;
        $inventory->load('items.consumable');

        return view('consumable-inventories.show', compact('inventory'));
    }

    public function settle(ConsumableInventory $consumable_inventory, ConsumableStockService $service)
    {
        $user = auth()->user();

        try {
            $service->settleInventory($consumable_inventory, $user->name, $user->id);
            return redirect()->route('consumable-inventories.show', $consumable_inventory)
                ->with('success', '盘点结算完成，库存已修正');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
