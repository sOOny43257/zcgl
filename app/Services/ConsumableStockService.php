<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableIntakeItem;
use App\Models\ConsumableUsage;
use App\Models\ConsumableInventory;
use App\Models\ConsumableInventoryItem;
use App\Models\ConsumableLog;
use Illuminate\Support\Facades\DB;

class ConsumableStockService
{
    /**
     * Complete an intake order: add stock for all items.
     */
    public function completeIntake(ConsumableIntakeOrder $order): void
    {
        if (!$order->isDraft()) {
            throw new \InvalidArgumentException('只有草稿状态的入库单才能完成');
        }

        if ($order->items->isEmpty()) {
            throw new \InvalidArgumentException('入库单没有明细，无法完成');
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $consumable = Consumable::where('id', $item->consumable_id)->lockForUpdate()->first();
                $oldStock = $consumable->current_stock;
                $consumable->increment('current_stock', $item->quantity);
                $consumable->refresh();

                ConsumableLog::create([
                    'consumable_id' => $consumable->id,
                    'consumable_name' => $consumable->name,
                    'user_id' => $order->operator_id,
                    'user_name' => $order->operator_name,
                    'action' => 'intake_complete',
                    'description' => "入库单 {$order->order_no} 完成，入库 +{$item->quantity}，库存 {$oldStock}→{$consumable->current_stock}",
                    'old_stock' => $oldStock,
                    'new_stock' => $consumable->current_stock,
                    'reference_type' => 'consumable_intake_order',
                    'reference_id' => $order->id,
                    'created_at' => now(),
                ]);
            }

            $order->update(['status' => 'completed']);
        });
    }

    /**
     * Cancel an intake order (only draft orders can be cancelled).
     */
    public function cancelIntake(ConsumableIntakeOrder $order, string $operatorName, ?int $operatorId = null): void
    {
        if (!$order->isDraft()) {
            throw new \InvalidArgumentException('只有草稿状态的入库单才能作废');
        }

        DB::transaction(function () use ($order, $operatorName, $operatorId) {
            $order->update(['status' => 'cancelled']);

            ConsumableLog::create([
                'consumable_id' => null,
                'consumable_name' => '入库单 ' . $order->order_no,
                'user_id' => $operatorId,
                'user_name' => $operatorName,
                'action' => 'cancel',
                'description' => "入库单 {$order->order_no} 已作废",
                'reference_type' => 'consumable_intake_order',
                'reference_id' => $order->id,
                'created_at' => now(),
            ]);
        });
    }

    /**
     * Create a usage record and deduct stock.
     */
    public function createUsage(array $data, string $operatorName, ?int $operatorId = null): ConsumableUsage
    {
        return DB::transaction(function () use ($data, $operatorName, $operatorId) {
            $consumable = Consumable::where('id', $data['consumable_id'])->lockForUpdate()->first();

            if ($consumable->current_stock < $data['quantity']) {
                throw new \InvalidArgumentException(
                    "库存不足：{$consumable->name} 当前库存 {$consumable->current_stock}，领用数量 {$data['quantity']}"
                );
            }

            $oldStock = $consumable->current_stock;
            $consumable->decrement('current_stock', $data['quantity']);
            $consumable->refresh();

            $usage = ConsumableUsage::create([
                'consumable_id' => $data['consumable_id'],
                'department_code' => $data['department_code'],
                'quantity' => $data['quantity'],
                'usage_date' => $data['usage_date'],
                'reason' => $data['reason'],
                'operator_id' => $operatorId,
                'operator_name' => $operatorName,
            ]);

            ConsumableLog::create([
                'consumable_id' => $consumable->id,
                'consumable_name' => $consumable->name,
                'user_id' => $operatorId,
                'user_name' => $operatorName,
                'action' => 'usage',
                'description' => "领用出库 -{$data['quantity']}，库存 {$oldStock}→{$consumable->current_stock}，部门: {$data['department_code']}，事由: {$data['reason']}",
                'old_stock' => $oldStock,
                'new_stock' => $consumable->current_stock,
                'reference_type' => 'consumable_usage',
                'reference_id' => $usage->id,
                'created_at' => now(),
            ]);

            return $usage;
        });
    }

    /**
     * Settle an inventory: adjust stock to match actual quantity.
     */
    public function settleInventory(ConsumableInventory $inventory, string $operatorName, ?int $operatorId = null): void
    {
        if (!$inventory->isDraft()) {
            throw new \InvalidArgumentException('只有草稿状态的盘点单才能结算');
        }

        DB::transaction(function () use ($inventory, $operatorName, $operatorId) {
            foreach ($inventory->items as $item) {
                if ($item->adjusted) {
                    continue; // Skip already adjusted items
                }

                $consumable = Consumable::where('id', $item->consumable_id)->lockForUpdate()->first();
                $oldStock = $consumable->current_stock;
                $diff = $item->actual_quantity - $oldStock;

                // Recalculate difference based on actual current stock
                $item->update([
                    'book_quantity' => $oldStock,
                    'difference' => $diff,
                    'adjusted' => true,
                ]);

                if ($diff !== 0) {
                    $consumable->update(['current_stock' => $item->actual_quantity]);

                    $action = $diff > 0 ? 'inventory_surplus' : 'inventory_deficit';
                    $sign = $diff > 0 ? '+' : '';
                    $reasonStr = $item->reason ? "，原因: {$item->reason}" : '';

                    ConsumableLog::create([
                        'consumable_id' => $consumable->id,
                        'consumable_name' => $consumable->name,
                        'user_id' => $operatorId,
                        'user_name' => $operatorName,
                        'action' => 'inventory_adjust',
                        'description' => "盘点{$action} {$sign}{$diff}，库存 {$oldStock}→{$item->actual_quantity}{$reasonStr}",
                        'old_stock' => $oldStock,
                        'new_stock' => $item->actual_quantity,
                        'reference_type' => 'consumable_inventory',
                        'reference_id' => $inventory->id,
                        'created_at' => now(),
                    ]);
                }
            }

            $inventory->update(['status' => 'completed']);
        });
    }

    /**
     * Get low-stock consumables for alert display.
     */
    public function getLowStockAlerts()
    {
        return Consumable::lowStock()->orderBy('current_stock')->get();
    }

    /**
     * Create multiple usage records in one transaction (batch usage).
     */
    public function createBatchUsage(array $items, string $departmentCode, string $usageDate, string $reason, string $operatorName, ?int $operatorId = null): array
    {
        return DB::transaction(function () use ($items, $departmentCode, $usageDate, $reason, $operatorName, $operatorId) {
            $usages = [];

            // Validate all stock first
            foreach ($items as $item) {
                $consumable = Consumable::where('id', $item['consumable_id'])->lockForUpdate()->first();
                if ($consumable->current_stock < $item['quantity']) {
                    throw new \InvalidArgumentException(
                        "库存不足：{$consumable->name} 当前库存 {$consumable->current_stock}，领用数量 {$item['quantity']}"
                    );
                }
            }

            // All stock sufficient, proceed with deduction
            foreach ($items as $item) {
                $consumable = Consumable::where('id', $item['consumable_id'])->first();
                $oldStock = $consumable->current_stock;
                $consumable->decrement('current_stock', $item['quantity']);
                $consumable->refresh();

                $usage = ConsumableUsage::create([
                    'consumable_id' => $item['consumable_id'],
                    'department_code' => $departmentCode,
                    'quantity' => $item['quantity'],
                    'usage_date' => $usageDate,
                    'reason' => $reason,
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                ]);

                ConsumableLog::create([
                    'consumable_id' => $consumable->id,
                    'consumable_name' => $consumable->name,
                    'user_id' => $operatorId,
                    'user_name' => $operatorName,
                    'action' => 'usage',
                    'description' => "领用出库 -{$item['quantity']}，库存 {$oldStock}→{$consumable->current_stock}，部门: {$departmentCode}，事由: {$reason}",
                    'old_stock' => $oldStock,
                    'new_stock' => $consumable->current_stock,
                    'reference_type' => 'consumable_usage',
                    'reference_id' => $usage->id,
                    'created_at' => now(),
                ]);

                $usages[] = $usage;
            }

            return $usages;
        });
    }
}
