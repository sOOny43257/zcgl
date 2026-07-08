<?php

namespace Tests\Feature\Consumable;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\DepartmentCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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

        DepartmentCode::create(['type' => 'department', 'code' => 'CW', 'name' => '财务部']);
        DepartmentCode::create(['type' => 'department', 'code' => 'RS', 'name' => '人事部']);
        DepartmentCode::create(['type' => 'hc_category', 'code' => 'BGWJ', 'name' => '办公文具']);
        DepartmentCode::create(['type' => 'hc_unit', 'code' => 'GE', 'name' => '个']);

        $c1 = Consumable::create(['name' => 'A4纸', 'category_code' => 'BGWJ', 'unit_code' => 'GE', 'current_stock' => 200]);
        $c2 = Consumable::create(['name' => '签字笔', 'category_code' => 'BGWJ', 'unit_code' => 'GE', 'current_stock' => 50]);

        // Create usage records for July 2026
        ConsumableUsage::create(['consumable_id' => $c1->id, 'department_code' => 'CW', 'quantity' => 30, 'usage_date' => '2026-07-05', 'reason' => '办公', 'operator_name' => '管理员']);
        ConsumableUsage::create(['consumable_id' => $c1->id, 'department_code' => 'RS', 'quantity' => 20, 'usage_date' => '2026-07-06', 'reason' => '办公', 'operator_name' => '管理员']);
        ConsumableUsage::create(['consumable_id' => $c2->id, 'department_code' => 'CW', 'quantity' => 10, 'usage_date' => '2026-07-07', 'reason' => '签字', 'operator_name' => '管理员']);
    }

    public function test_report_index_loads(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-reports?month=2026-07&type=department');
        $response->assertStatus(200);
    }

    public function test_department_report_returns_correct_data(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-reports?month=2026-07&type=department');
        $response->assertStatus(200);
        $response->assertSee('财务部');
        $response->assertSee('人事部');
    }

    public function test_ranking_report(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-reports?month=2026-07&type=ranking');
        $response->assertStatus(200);
        $response->assertSee('A4纸');
    }

    public function test_turnover_report(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-reports?month=2026-07&type=turnover');
        $response->assertStatus(200);
        $response->assertSee('A4纸');
    }

    public function test_csv_export(): void
    {
        $response = $this->actingAs($this->admin)->get('/consumable-reports/export?month=2026-07&type=department');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
