<?php

namespace Tests\Feature\Consumable;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\DepartmentCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UsageTest extends TestCase
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
        DepartmentCode::create(['type' => 'department', 'code' => 'CW', 'name' => '财务部']);

        $this->consumable = Consumable::create([
            'name' => '测试耗材',
            'category_code' => 'BGWJ',
            'unit_code' => 'GE',
            'current_stock' => 100,
        ]);
    }

    public function test_admin_can_create_batch_usage(): void
    {
        $c2 = Consumable::create([
            'name' => '签字笔',
            'category_code' => 'BGWJ',
            'unit_code' => 'GE',
            'current_stock' => 50,
        ]);

        $response = $this->actingAs($this->admin)->post('/consumable-usages', [
            'department_code' => 'CW',
            'usage_date' => '2026-07-08',
            'reason' => '日常办公需要',
            'items' => [
                ['consumable_id' => $this->consumable->id, 'quantity' => 20],
                ['consumable_id' => $c2->id, 'quantity' => 10],
            ],
        ]);

        $response->assertRedirect();

        $this->consumable->refresh();
        $this->assertEquals(80, $this->consumable->current_stock);

        $c2->refresh();
        $this->assertEquals(40, $c2->current_stock);

        $this->assertDatabaseHas('consumable_usages', [
            'consumable_id' => $this->consumable->id,
            'department_code' => 'CW',
            'quantity' => 20,
        ]);
        $this->assertDatabaseHas('consumable_usages', [
            'consumable_id' => $c2->id,
            'department_code' => 'CW',
            'quantity' => 10,
        ]);
    }

    public function test_batch_usage_exceeding_stock_returns_error(): void
    {
        $response = $this->actingAs($this->admin)->post('/consumable-usages', [
            'department_code' => 'CW',
            'usage_date' => '2026-07-08',
            'reason' => '大量领用',
            'items' => [
                ['consumable_id' => $this->consumable->id, 'quantity' => 200],
            ],
        ]);

        $response->assertSessionHas('error');

        $this->consumable->refresh();
        $this->assertEquals(100, $this->consumable->current_stock);
    }

    public function test_batch_usage_partial_failure_rolls_back(): void
    {
        $c2 = Consumable::create([
            'name' => '签字笔',
            'category_code' => 'BGWJ',
            'unit_code' => 'GE',
            'current_stock' => 5,
        ]);

        $response = $this->actingAs($this->admin)->post('/consumable-usages', [
            'department_code' => 'CW',
            'usage_date' => '2026-07-08',
            'reason' => '测试',
            'items' => [
                ['consumable_id' => $this->consumable->id, 'quantity' => 10],
                ['consumable_id' => $c2->id, 'quantity' => 100], // exceeds stock
            ],
        ]);

        $response->assertSessionHas('error');

        // Both should be unchanged (transaction rolled back)
        $this->consumable->refresh();
        $this->assertEquals(100, $this->consumable->current_stock);
        $c2->refresh();
        $this->assertEquals(5, $c2->current_stock);
    }

    public function test_usage_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post('/consumable-usages', [
            'department_code' => '',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['department_code', 'usage_date', 'reason', 'items']);
    }

    public function test_admin_can_view_usage_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-usages');
        $response->assertStatus(200);
    }
}
