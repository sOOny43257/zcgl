<?php

namespace Tests\Feature\Consumable;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableInventory;
use App\Models\ConsumableInventoryItem;
use App\Models\DepartmentCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Consumable $consumable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => '管理员',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        DepartmentCode::create(['type' => 'hc_category', 'code' => 'BGWJ', 'name' => '办公文具']);
        DepartmentCode::create(['type' => 'hc_unit', 'code' => 'GE', 'name' => '个']);

        $this->consumable = Consumable::create([
            'name' => '测试耗材',
            'category_code' => 'BGWJ',
            'unit_code' => 'GE',
            'current_stock' => 100,
            'min_stock' => 20,
        ]);
    }

    public function test_admin_can_create_inventory(): void
    {
        $response = $this->actingAs($this->admin)->post('/consumable-inventories', [
            'inventory_date' => '2026-07-08',
            'items' => [
                ['consumable_id' => $this->consumable->id, 'actual_quantity' => 95, 'reason' => '轻微损耗'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumable_inventories', ['status' => 'draft']);
        $this->assertDatabaseHas('consumable_inventory_items', [
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 95,
            'difference' => -5,
        ]);
    }

    public function test_settle_inventory_adjusts_stock(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-TEST',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->admin->id,
            'operator_name' => $this->admin->name,
            'status' => 'draft',
        ]);

        ConsumableInventoryItem::create([
            'inventory_id' => $inventory->id,
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 85,
            'difference' => -15,
            'reason' => '盘点损耗',
        ]);

        $response = $this->actingAs($this->admin)->post("/consumable-inventories/{$inventory->id}/settle");
        $response->assertRedirect();

        $this->consumable->refresh();
        $this->assertEquals(85, $this->consumable->current_stock);

        $inventory->refresh();
        $this->assertEquals('completed', $inventory->status);
    }

    public function test_settle_inventory_creates_log(): void
    {
        $inventory = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260708-TEST2',
            'inventory_date' => '2026-07-08',
            'operator_id' => $this->admin->id,
            'operator_name' => $this->admin->name,
            'status' => 'draft',
        ]);

        ConsumableInventoryItem::create([
            'inventory_id' => $inventory->id,
            'consumable_id' => $this->consumable->id,
            'book_quantity' => 100,
            'actual_quantity' => 110,
            'difference' => 10,
            'reason' => '盘盈',
        ]);

        $this->actingAs($this->admin)->post("/consumable-inventories/{$inventory->id}/settle");

        $this->assertDatabaseHas('consumable_logs', [
            'consumable_id' => $this->consumable->id,
            'action' => 'inventory_adjust',
            'old_stock' => 100,
            'new_stock' => 110,
        ]);
    }
}
