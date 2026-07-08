<?php

namespace Tests\Feature\Consumable;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableIntakeItem;
use App\Models\DepartmentCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IntakeTest extends TestCase
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
        DepartmentCode::create(['type' => 'supplier', 'code' => 'JD', 'name' => '京东']);

        $this->consumable = Consumable::create([
            'name' => '测试耗材',
            'category_code' => 'BGWJ',
            'unit_code' => 'GE',
            'current_stock' => 50,
        ]);
    }

    public function test_admin_can_view_intake_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-intakes');
        $response->assertStatus(200);
    }

    public function test_admin_can_create_intake_order(): void
    {
        $response = $this->actingAs($this->admin)->post('/consumable-intakes', [
            'intake_date' => '2026-07-08',
            'supplier_code' => 'JD',
            'items' => [
                ['consumable_id' => $this->consumable->id, 'quantity' => 30, 'unit_price' => 10.50],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('consumable_intake_orders', ['status' => 'draft']);
        $this->assertDatabaseHas('consumable_intake_items', [
            'consumable_id' => $this->consumable->id,
            'quantity' => 30,
        ]);
    }

    public function test_complete_intake_updates_stock(): void
    {
        $order = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-TEST',
            'intake_date' => '2026-07-08',
            'operator_id' => $this->admin->id,
            'operator_name' => $this->admin->name,
            'status' => 'draft',
        ]);

        ConsumableIntakeItem::create([
            'intake_order_id' => $order->id,
            'consumable_id' => $this->consumable->id,
            'quantity' => 40,
        ]);

        $response = $this->actingAs($this->admin)->post("/consumable-intakes/{$order->id}/complete");
        $response->assertRedirect();

        $this->consumable->refresh();
        $this->assertEquals(90, $this->consumable->current_stock);
    }

    public function test_non_admin_cannot_access_intakes(): void
    {
        $user = User::create([
            'name' => '普通用户',
            'username' => 'user',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/consumable-intakes');
        $response->assertStatus(403);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post('/consumable-intakes', [
            'intake_date' => '',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['intake_date', 'items']);
    }
}
