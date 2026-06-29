<?php

namespace Tests\Feature\CsvImport;

use App\Models\Asset;
use App\Models\DepartmentCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\CsvTestHelper;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase, CsvTestHelper;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDepartmentCodes();
        $this->admin = $this->createAdmin();
    }

    // ==================== I1. 非管理员访问 ====================

    public function test_i1_normal_user_cannot_access_parse_csv()
    {
        $user = $this->createNormalUser();
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ]);
        $response = $this->actingAs($user)
            ->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(403);
    }

    public function test_i1_normal_user_cannot_access_batch_import()
    {
        $user = $this->createNormalUser();
        $rows = [
            [
                'asset_code' => '', 'financial_code' => '', 'name' => '测试',
                'department' => '办公室', 'room' => '301', 'ip' => '192.168.1.100',
                'mac' => 'AA:BB:CC:DD:EE:FF', 'sn' => '', 'brand' => '', 'model' => '',
                'category' => '台式计算机（国产）', 'status' => '在用', 'user' => '', 'remarks' => '',
            ],
        ];
        $response = $this->actingAs($user)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $response->assertStatus(403);
    }

    public function test_i1_normal_user_cannot_access_legacy_import()
    {
        $user = $this->createNormalUser();
        $upload = $this->createCsvUpload(['name', 'ip', 'mac'], [['Test', '192.168.1.1', 'AA:BB:CC:DD:EE:FF']]);
        $response = $this->actingAs($user)
            ->post('/assets/import', ['csv_file' => $upload]);
        $response->assertStatus(403);
    }

    // ==================== I2. 未登录访问 ====================

    public function test_i2_guest_redirected_from_parse_csv()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow(),
        ]);
        $response = $this->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(401);
    }

    public function test_i2_guest_redirected_from_batch_import()
    {
        $response = $this->postJson('/assets/import/batch', ['rows' => [['name' => 'test']]]);
        $response->assertStatus(401);
    }

    public function test_i2_guest_redirected_from_legacy_import()
    {
        $upload = $this->createCsvUpload(['name', 'ip', 'mac'], [['Test', '192.168.1.1', 'AA:BB:CC:DD:EE:FF']]);
        $response = $this->post('/assets/import', ['csv_file' => $upload]);
        $response->assertRedirectContains('login');
    }

    // ==================== I3-I6. 安全测试 ====================

    public function test_i3_sql_injection_safe()
    {
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([3 => "'; DROP TABLE assets;--"]),
        ]);
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals("'; DROP TABLE assets;--", $response->json('rows.0.name'));

        // 也通过 batchImport 入库验证
        $rows = [
            [
                'asset_code' => '', 'financial_code' => '', 'name' => "'; DROP TABLE assets;--",
                'department' => '办公室', 'room' => '301', 'ip' => '', 'mac' => '',
                'sn' => '', 'brand' => '', 'model' => '', 'category' => '台式计算机（国产）',
                'status' => '在用', 'user' => '', 'remarks' => '',
            ],
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(1, $response->json('imported'));
        $this->assertDatabaseHas('assets', ['name' => "'; DROP TABLE assets;--"]);
        // assets 表应该还存在
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_i4_xss_stored_as_is()
    {
        $xssPayload = '<script>alert(1)</script>';
        // Test via batchImport which doesn't do MIME check
        $rows = [
            [
                'asset_code' => '', 'financial_code' => '', 'name' => $xssPayload,
                'department' => '办公室', 'room' => '301', 'ip' => '', 'mac' => '',
                'sn' => '', 'brand' => '', 'model' => '', 'category' => '台式计算机（国产）',
                'status' => '在用', 'user' => '', 'remarks' => '',
            ],
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(1, $response->json('imported'));
        $asset = Asset::first();
        $this->assertEquals($xssPayload, $asset->name);
    }

    public function test_i5_csv_formula_injection()
    {
        $formula = '=cmd|\' /C calc\'!A1';
        $upload = $this->createCsvUpload($this->chineseHeaders(), [
            $this->assetRow([3 => $formula]),
        ]);
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/parse', ['csv_file' => $upload]);
        $response->assertStatus(200);
        $this->assertEquals($formula, $response->json('rows.0.name'));
    }

    public function test_i6_very_long_remarks_field()
    {
        $longRemarks = str_repeat('备注内容测试。', 1000); // ~10000 chars
        $rows = [
            [
                'asset_code' => '', 'financial_code' => '', 'name' => '长备注测试',
                'department' => '办公室', 'room' => '301', 'ip' => '', 'mac' => '',
                'sn' => '', 'brand' => '', 'model' => '', 'category' => '台式计算机（国产）',
                'status' => '在用', 'user' => '', 'remarks' => $longRemarks,
            ],
        ];
        $response = $this->actingAs($this->admin)
            ->postJson('/assets/import/batch', ['rows' => $rows]);
        $this->assertEquals(1, $response->json('imported'));
        $asset = Asset::first();
        $this->assertEquals($longRemarks, $asset->remarks);
    }
}
