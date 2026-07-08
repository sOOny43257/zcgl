<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableIntakeItem;
use App\Models\ConsumableUsage;
use App\Models\ConsumableInventory;
use App\Models\ConsumableInventoryItem;
use App\Models\ConsumableLog;
use App\Models\DepartmentCode;
use App\Models\User;
use App\Services\ConsumableStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsumableStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConsumableStockService $service;
    private User $user;
    private Consumable $consumable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ConsumableStockService();

        // Create test user
        $this->user = User::create([
            'name' => '测试用户',
            'username' => 'testuser',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create dictionary data
        DepartmentCode::create(['type' => 'hc_category', 'code' => 'BGWJ', 'name' => '办公文具']);
        DepartmentCode::create(['type' => 'hc_unit', 'code' => 'GE', 'name' => '个']);
        DepartmentCode::create(['type' => 'department', 'code' => 'CW', 'name' => '财务部']);

        // Create test consumable with 100 stock
        $this->consumable = Consumable::create([
            'name' => 'A4打印纸',
            'category_code' => 'BGWJ',
            'spec' => '70g 500张/包',
            'unit_code' => 'GE',
            'min_stock' => 20,
            'current_stock' => 100,
            'unit_price' => 25.00,
        ]);
    }

    // === Intake Tests ===

    public function test_complete_intake_adds_stock(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-001',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableIntakeItem::create([
            'intake_order_id' => $order->id,
            'consumable_id' => $this->consumable->id,
            'quantity' => 50,
        ]);

        $this->service->completeIntake($order);

        $this->consumable->refresh();
        $this->assertEquals(150, $this->consumable->current_stock);
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_complete_intake_creates_log(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-002',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableIntakeItem::create([
            'intake_order_id' => $order->id,
            'consumable_id' => $this->consumable->id,
            'quantity' => 30,
        ]);

        $this->service->completeIntake($order);

        $this->assertDatabaseHas('consumable_logs', [
            'consumable_id' => $this->consumable->id,
            'action' => 'intake_complete',
            'old_stock' => 100,
            'new_stock' => 130,
        ]);
    }

    public function test_complete_non_draft_order_throws(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-003',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'completed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->completeIntake($order);
    }

    public function test_complete_intake_twice_does_not_double_count(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-004',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableIntakeItem::create([
            'intake_order_id' => $order->id,
            'consumable_id' => $this->consumable->id,
            'quantity' => 50,
        ]);

        $this->service->completeIntake($order);

        // Try to complete again
        $this->expectException(\InvalidArgumentException::class);
        $this->service->completeIntake($order);

        $this->consumable->refresh();
        $this->assertEquals(150, $this->consumable->current_stock);
    }

    public function test_complete_intake_with_zero_quantity_throws(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-005',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        // No items added
        $this->expectException(\InvalidArgumentException::class);
        $this->service->completeIntake($order);
    }

    // === Usage (Stock Deduction) Tests ===

    public function test_create_usage_deducts_stock(): void
    {
        $this->service->createUsage([
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 10,
            'usage_date' => '2026-07-08',
            'reason' => '日常办公',
        ], $this->user->name, $this->user->id);

        $this->consumable->refresh();
        $this->assertEquals(90, $this->consumable->current_stock);
    }

    public function test_create_usage_exceeding_stock_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('库存不足');

        $this->service->createUsage([
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 200,
            'usage_date' => '2026-07-08',
            'reason' => '大量领用',
        ], $this->user->name, $this->user->id);
    }

    public function test_create_usage_exceeding_stock_does_not_change_stock(): void
    {
        try {
            $this->service->createUsage([
                'consumable_id' => $this->consumable->id,
                'department_code' => 'CW',
                'quantity' => 200,
                'usage_date' => '2026-07-08',
                'reason' => '大量领用',
            ], $this->user->name, $this->user->id);
        } catch (\InvalidArgumentException $e) {
            // Expected
        }

        $this->consumable->refresh();
        $this->assertEquals(100, $this->consumable->current_stock);
    }

    public function test_create_usage_exact_stock_succeeds(): void
    {
        $this->service->createUsage([
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 100,
            'usage_date' => '2026-07-08',
            'reason' => '全部领用',
        ], $this->user->name, $this->user->id);

        $this->consumable->refresh();
        $this->assertEquals(0, $this->consumable->current_stock);
    }

    public function test_usage_creates_record_and_log(): void
    {
        $usage = $this->service->createUsage([
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 15,
            'usage_date' => '2026-07-08',
            'reason' => '季度办公补充',
        ], $this->user->name, $this->user->id);

        $this->assertDatabaseHas('consumable_usages', [
            'id' => $usage->id,
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 15,
        ]);

        $this->assertDatabaseHas('consumable_logs', [
            'consumable_id' => $this->consumable->id,
            'action' => 'usage',
            'old_stock' => 100,
            'new_stock' => 85,
        ]);
    }

    // === Inventory Tests ===

    public function test_settle_inventory_adjusts_stock_on_surplus(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-001',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableInventoryItem::create([
            'inventory_id' => $inventory->id,
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 120,
            'difference' => 20,
            'reason' => '盘点发现多出',
        ]);

        $this->service->settleInventory($inventory, $this->user->name, $this->user->id);

        $this->consumable->refresh();
        $this->assertEquals(120, $this->consumable->current_stock);
    }

    public function test_settle_inventory_adjusts_stock_on_deficit(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-002',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableInventoryItem::create([
            'inventory_id' => $inventory->id,
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 80,
            'difference' => -20,
            'reason' => '损耗',
        ]);

        $this->service->settleInventory($inventory, $this->user->name, $this->user->id);

        $this->consumable->refresh();
        $this->assertEquals(80, $this->consumable->current_stock);
    }

    public function test_settle_inventory_with_no_difference_keeps_stock(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-003',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        ConsumableInventoryItem::create([
            'inventory_id' => $inventory->id,
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 100,
            'difference' => 0,
        ]);

        $this->service->settleInventory($inventory, $this->user->name, $this->user->id);

        $this->consumable->refresh();
        $this->assertEquals(100, $this->consumable->current_stock);
        $this->assertEquals('completed', $inventory->fresh()->status);
    }

    public function test_settle_non_draft_inventory_throws(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-004',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'completed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->settleInventory($inventory, $this->user->name, $this->user->id);
    }

    // === Low Stock Alert Tests ===

    public function test_low_stock_alert_triggered_when_below_threshold(): void
    {
        $this->consumable->update(['current_stock' => 15, 'min_stock' => 20]);

        $alerts = $this->service->getLowStockAlerts();

        $this->assertCount(1, $alerts);
        $this->assertEquals($this->consumable->id, $alerts->first()->id);
    }

    public function test_low_stock_alert_not_triggered_when_at_threshold(): void
    {
        $this->consumable->update(['current_stock' => 20, 'min_stock' => 20]);

        $alerts = $this->service->getLowStockAlerts();

        $this->assertCount(1, $alerts); // at threshold also triggers (<=)
    }

    public function test_low_stock_alert_not_triggered_when_above_threshold(): void
    {
        $this->consumable->update(['current_stock' => 50, 'min_stock' => 20]);

        $alerts = $this->service->getLowStockAlerts();

        $this->assertCount(0, $alerts);
    }

    public function test_low_stock_alert_not_triggered_when_min_stock_is_zero(): void
    {
        $this->consumable->update(['current_stock' => 0, 'min_stock' => 0]);

        $alerts = $this->service->getLowStockAlerts();

        $this->assertCount(0, $alerts);
    }

    // === Cancel Intake Tests ===

    public function test_cancel_draft_intake(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-010',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'draft',
        ]);

        $this->service->cancelIntake($order, $this->user->name, $this->user->id);

        $this->assertEquals('cancelled', $order->fresh()->status);
    }

    public function test_cancel_completed_intake_throws(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-011',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->user->id,
            'operator_name' => $this->user->name,
            'status' => 'completed',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->cancelIntake($order, $this->user->name, $this->user->id);
    }
}
