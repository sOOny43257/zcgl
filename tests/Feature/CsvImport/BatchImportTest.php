<?php

namespace Tests\Feature\CsvImport;

use App\Models\Asset;
use App\Models\DepartmentCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\CsvTestHelper;

class BatchImportTest extends TestCase
{
    use RefreshDatabase, CsvTestHelper;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDepartmentCodes();
        $this->admin = $this->createAdmin();
    }

    // ==================== G. batchImport 提交 ====================

    public function test_g1_empty_rows_array()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => []]);
        $response->assertStatus(422);
    }

    public function test_g2_missing_rows_field()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', []);
        $response->assertStatus(422);
    }

    public function test_g3_invalid_ip_in_row()
    {
        $rows = [
            $this->makeValidRow(['name' => '正常资产']),
            $this->makeValidRow(['name' => '非法IP', 'ip' => 'invalid-ip']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(1, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
        $this->assertNotEmpty($data['errors']);
        $this->assertStringContainsString('IP格式不合法', $data['errors'][0]);
    }

    public function test_g4_invalid_dictionary_value()
    {
        $rows = [
            $this->makeValidRow(['department' => '不存在的部门']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
        $this->assertStringContainsString('不在数据字典中', $data['errors'][0]);
    }

    public function test_g5_duplicate_asset_code_skipped()
    {
        // 先创建一个已有 asset_code 的资产
        Asset::create([
            'asset_code' => 'C26001',
            'name' => '已有资产',
            'category' => '台式计算机（国产）',
            'status' => '在用',
        ]);

        $rows = [
            $this->makeValidRow(['asset_code' => 'C26001', 'name' => '重复编号']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
        $this->assertStringContainsString('C26001', $data['errors'][0]);
        $this->assertStringContainsString('已存在', $data['errors'][0]);
    }

    public function test_g6_all_empty_fields_row_skipped()
    {
        $rows = [
            [
                'asset_code' => '', 'financial_code' => '', 'name' => '',
                'department' => '', 'room' => '', 'ip' => '', 'mac' => '',
                'sn' => '', 'brand' => '', 'model' => '', 'category' => '',
                'status' => '', 'user' => '', 'remarks' => '',
            ],
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
    }

    public function test_g7_empty_asset_code_auto_generated()
    {
        $rows = [
            $this->makeValidRow(['asset_code' => '']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('imported'));
        // 数据库中应有自动生成的 asset_code
        $asset = Asset::first();
        $this->assertNotEmpty($asset->asset_code);
    }

    public function test_g8_too_long_field_value()
    {
        $rows = [
            $this->makeValidRow(['name' => str_repeat('超', 201)]), // max 200
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(0, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
        $this->assertStringContainsString('name', $data['errors'][0]);
        $this->assertStringContainsString('200', $data['errors'][0]);
    }

    public function test_g9_invalid_dictionary_in_batch()
    {
        $rows = [
            $this->makeValidRow(['name' => '合法', 'department' => '办公室']),
            $this->makeValidRow(['name' => '非法部门', 'department' => '不存在的部门']),
            $this->makeValidRow(['name' => '合法2', 'department' => '信息中心']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(2, $data['imported']);
        $this->assertEquals(1, $data['skipped']);
    }

    // ==================== 额外 batchImport 测试 ====================

    public function test_successful_import_creates_assets()
    {
        $rows = [
            $this->makeValidRow(['name' => '资产A', 'department' => '办公室']),
            $this->makeValidRow(['name' => '资产B', 'department' => '信息中心']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'imported' => 2,
            'skipped' => 0,
        ]);
        $this->assertDatabaseCount('assets', 2);
    }

    public function test_category_defaults_when_empty()
    {
        $rows = [
            $this->makeValidRow(['category' => '']),
        ];
        $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $asset = Asset::first();
        $this->assertEquals('台式计算机（非国产）', $asset->category);
    }

    public function test_status_defaults_when_empty()
    {
        $rows = [
            $this->makeValidRow(['status' => '']),
        ];
        $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $asset = Asset::first();
        $this->assertEquals('在用', $asset->status);
    }

    public function test_ip_mac_null_when_empty()
    {
        $rows = [
            $this->makeValidRow(['ip' => '', 'mac' => '']),
        ];
        $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $asset = Asset::first();
        $this->assertNull($asset->ip);
        $this->assertNull($asset->mac);
    }

    public function test_dictionary_fields_converted_to_codes()
    {
        $rows = [
            $this->makeValidRow([
                'department' => '办公室',
                'category' => '打印机',
                'status' => '闲置',
            ]),
        ];
        $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $asset = Asset::first();
        $this->assertEquals('01', $asset->department);
        $this->assertEquals('C03', $asset->category);
        $this->assertEquals('S02', $asset->status);
    }

    public function test_mac_format_validation_in_batch()
    {
        $rows = [
            $this->makeValidRow(['mac' => 'INVALID-MAC']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertEquals(1, $response->json('skipped'));
        $this->assertStringContainsString('MAC格式不合法', $response->json('errors.0'));
    }

    public function test_valid_mac_format_accepted()
    {
        $rows = [
            $this->makeValidRow(['mac' => 'AA-BB-CC-DD-EE-FF']),
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(1, $response->json('imported'));
    }

    public function test_financial_code_too_long()
    {
        $rows = [
            $this->makeValidRow(['financial_code' => str_repeat('X', 51)]), // max 50
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(0, $response->json('imported'));
        $this->assertStringContainsString('financial_code', $response->json('errors.0'));
    }

    /**
     * 创建一个标准合法行数据
     */
    private function makeValidRow(array $overrides = []): array
    {
        $defaults = [
            'asset_code' => '',
            'financial_code' => '',
            'name' => '测试资产',
            'department' => '办公室',
            'room' => '301',
            'ip' => '192.168.1.100',
            'mac' => 'AA:BB:CC:DD:EE:FF',
            'sn' => 'SN001',
            'brand' => '联想',
            'model' => '启天M428',
            'category' => '台式计算机（国产）',
            'status' => '在用',
            'user' => '张三',
            'remarks' => '',
        ];
        return array_merge($defaults, $overrides);
    }
}
