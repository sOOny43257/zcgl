<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\DepartmentCode;
use App\Models\Consumable;
use App\Models\ConsumableIntakeOrder;
use App\Models\ConsumableIntakeItem;
use App\Models\ConsumableUsage;
use App\Models\ConsumableInventory;
use App\Models\ConsumableInventoryItem;
use App\Models\ConsumableLog;
use Illuminate\Database\Seeder;

class ConsumableDemoSeeder extends Seeder
{
    public function run(): void
    {
        // ========== 1. 基础字典数据 ==========

        // 部门
        $departments = [
            ['code' => 'CW', 'name' => '财务科'],
            ['code' => 'RS', 'name' => '人事科'],
            ['code' => 'ZHS', 'name' => '综合税'],
            ['code' => 'SHS', 'name' => '所得税'],
            ['code' => 'LTS', 'name' => '流转税'],
            ['code' => 'JC', 'name' => '稽查局'],
            ['code' => 'XX', 'name' => '信息中心'],
            ['code' => 'BG', 'name' => '办公室'],
        ];
        foreach ($departments as $d) {
            DepartmentCode::updateOrCreate(['type' => 'department', 'code' => $d['code']], ['name' => $d['name']]);
        }

        // 资产类别
        $categories = [
            ['code' => 'JSJ', 'name' => '台式计算机（国产）'],
            ['code' => 'JSJF', 'name' => '台式计算机（非国产）'],
            ['code' => 'DYJ', 'name' => '打印机'],
            ['code' => 'JHJ', 'name' => '交换机'],
            ['code' => 'XSQ', 'name' => '显示器'],
            ['code' => 'FWQ', 'name' => '服务器'],
            ['code' => 'LYQ', 'name' => '路由器'],
            ['code' => 'QT', 'name' => '其他'],
        ];
        foreach ($categories as $c) {
            DepartmentCode::updateOrCreate(['type' => 'category', 'code' => $c['code']], ['name' => $c['name']]);
        }

        // 资产状态
        $statuses = [
            ['code' => 'ZY', 'name' => '在用'],
            ['code' => 'XZ', 'name' => '闲置'],
            ['code' => 'WX', 'name' => '维修'],
            ['code' => 'JIE', 'name' => '借用'],
            ['code' => 'DBF', 'name' => '待报废'],
            ['code' => 'BF', 'name' => '报废'],
        ];
        foreach ($statuses as $s) {
            DepartmentCode::updateOrCreate(['type' => 'status', 'code' => $s['code']], ['name' => $s['name']]);
        }

        // 耗材分类
        $hcCategories = [
            ['code' => 'BGWJ', 'name' => '办公文具'],
            ['code' => 'ITPJ', 'name' => 'IT配件'],
            ['code' => 'QJYP', 'name' => '清洁用品'],
            ['code' => 'BGHC', 'name' => '办公耗材'],
            ['code' => 'LBYP', 'name' => '劳保用品'],
            ['code' => 'QT', 'name' => '其他'],
        ];
        foreach ($hcCategories as $c) {
            DepartmentCode::updateOrCreate(['type' => 'hc_category', 'code' => $c['code']], ['name' => $c['name']]);
        }

        // 耗材单位
        $units = [
            ['code' => 'GE', 'name' => '个'],
            ['code' => 'ZHI', 'name' => '支'],
            ['code' => 'BAO', 'name' => '包'],
            ['code' => 'XIANG', 'name' => '箱'],
            ['code' => 'PING', 'name' => '瓶'],
            ['code' => 'JUAN', 'name' => '卷'],
            ['code' => 'TAO', 'name' => '套'],
            ['code' => 'TAI', 'name' => '台'],
            ['code' => 'JIAN', 'name' => '件'],
        ];
        foreach ($units as $u) {
            DepartmentCode::updateOrCreate(['type' => 'hc_unit', 'code' => $u['code']], ['name' => $u['name']]);
        }

        // 供应商
        $suppliers = [
            ['code' => 'JD', 'name' => '京东'],
            ['code' => 'TB', 'name' => '淘宝'],
            ['code' => 'WD', 'name' => '文达办公'],
            ['code' => 'HX', 'name' => '华夏商城'],
            ['code' => 'QTGYS', 'name' => '其他供应商'],
        ];
        foreach ($suppliers as $s) {
            DepartmentCode::updateOrCreate(['type' => 'supplier', 'code' => $s['code']], ['name' => $s['name']]);
        }

        // ========== 2. 确保管理员存在 ==========
        $admin = User::firstOrCreate(
            ['username' => 'admin'],
            ['name' => 'Admin', 'email' => 'admin@zcgl.local', 'password' => bcrypt('admin123'), 'role' => 'admin']
        );

        // ========== 3. 耗材主数据 ==========
        $consumables = [
            ['name' => 'A4打印纸', 'category_code' => 'BGHC', 'spec' => '70g 500张/包', 'unit_code' => 'BAO', 'supplier_code' => 'JD', 'min_stock' => 20, 'current_stock' => 0, 'unit_price' => 28.00],
            ['name' => 'A3打印纸', 'category_code' => 'BGHC', 'spec' => '70g 500张/包', 'unit_code' => 'BAO', 'supplier_code' => 'JD', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 45.00],
            ['name' => '黑色签字笔', 'category_code' => 'BGWJ', 'spec' => '0.5mm 按动式', 'unit_code' => 'ZHI', 'supplier_code' => 'WD', 'min_stock' => 50, 'current_stock' => 0, 'unit_price' => 3.50],
            ['name' => '红色签字笔', 'category_code' => 'BGWJ', 'spec' => '0.5mm 按动式', 'unit_code' => 'ZHI', 'supplier_code' => 'WD', 'min_stock' => 20, 'current_stock' => 0, 'unit_price' => 3.50],
            ['name' => '订书机', 'category_code' => 'BGWJ', 'spec' => '中号 省力型', 'unit_code' => 'TAI', 'supplier_code' => 'WD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 25.00],
            ['name' => '订书钉', 'category_code' => 'BGWJ', 'spec' => '24/6 1000枚/盒', 'unit_code' => 'GE', 'supplier_code' => 'WD', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 5.00],
            ['name' => '文件夹', 'category_code' => 'BGWJ', 'spec' => 'A4 双夹', 'unit_code' => 'GE', 'supplier_code' => 'TB', 'min_stock' => 30, 'current_stock' => 0, 'unit_price' => 4.00],
            ['name' => '档案盒', 'category_code' => 'BGWJ', 'spec' => '标准 A4', 'unit_code' => 'GE', 'supplier_code' => 'TB', 'min_stock' => 20, 'current_stock' => 0, 'unit_price' => 8.00],
            ['name' => '墨盒（黑色）', 'category_code' => 'ITPJ', 'spec' => 'HP 12A 兼容', 'unit_code' => 'GE', 'supplier_code' => 'JD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 89.00],
            ['name' => '墨盒（彩色）', 'category_code' => 'ITPJ', 'spec' => 'HP 12A 兼容', 'unit_code' => 'GE', 'supplier_code' => 'JD', 'min_stock' => 3, 'current_stock' => 0, 'unit_price' => 120.00],
            ['name' => '网线', 'category_code' => 'ITPJ', 'spec' => 'CAT6 3米', 'unit_code' => 'GENG', 'supplier_code' => 'JD', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 8.00],
            ['name' => '鼠标', 'category_code' => 'ITPJ', 'spec' => 'USB 有线', 'unit_code' => 'GE', 'supplier_code' => 'JD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 35.00],
            ['name' => '键盘', 'category_code' => 'ITPJ', 'spec' => 'USB 有线 标准', 'unit_code' => 'GE', 'supplier_code' => 'JD', 'min_stock' => 3, 'current_stock' => 0, 'unit_price' => 55.00],
            ['name' => 'U盘', 'category_code' => 'ITPJ', 'spec' => '32GB USB3.0', 'unit_code' => 'GE', 'supplier_code' => 'JD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 45.00],
            ['name' => '垃圾袋', 'category_code' => 'QJYP', 'spec' => '大号 黑色 50只/卷', 'unit_code' => 'JUAN', 'supplier_code' => 'TB', 'min_stock' => 15, 'current_stock' => 0, 'unit_price' => 12.00],
            ['name' => '洗手液', 'category_code' => 'QJYP', 'spec' => '500ml', 'unit_code' => 'PING', 'supplier_code' => 'TB', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 15.00],
            ['name' => '消毒液', 'category_code' => 'QJYP', 'spec' => '5L', 'unit_code' => 'PING', 'supplier_code' => 'TB', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 35.00],
            ['name' => '抽纸', 'category_code' => 'QJYP', 'spec' => '3层 120抽 24包/箱', 'unit_code' => 'XIANG', 'supplier_code' => 'JD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 55.00],
            ['name' => '一次性纸杯', 'category_code' => 'QJYP', 'spec' => '250ml 100只/包', 'unit_code' => 'BAO', 'supplier_code' => 'TB', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 18.00],
            ['name' => '碳粉', 'category_code' => 'BGHC', 'spec' => 'HP 通用 200g', 'unit_code' => 'PING', 'supplier_code' => 'HX', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 65.00],
            ['name' => '色带', 'category_code' => 'BGHC', 'spec' => 'LQ-630K 通用', 'unit_code' => 'GENG', 'supplier_code' => 'HX', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 25.00],
            ['name' => '口罩', 'category_code' => 'LBYP', 'spec' => '一次性 50只/盒', 'unit_code' => 'HE', 'supplier_code' => 'JD', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 22.00],
            ['name' => '手套', 'category_code' => 'LBYP', 'spec' => '一次性乳胶 100只/盒', 'unit_code' => 'HE', 'supplier_code' => 'JD', 'min_stock' => 5, 'current_stock' => 0, 'unit_price' => 28.00],
            ['name' => '便利贴', 'category_code' => 'BGWJ', 'spec' => '76×76mm 4色', 'unit_code' => 'TAO', 'supplier_code' => 'WD', 'min_stock' => 15, 'current_stock' => 0, 'unit_price' => 6.00],
            ['name' => '胶带', 'category_code' => 'BGWJ', 'spec' => '透明 48mm×100m', 'unit_code' => 'JUAN', 'supplier_code' => 'TB', 'min_stock' => 10, 'current_stock' => 0, 'unit_price' => 5.00],
        ];

        // Fix some unit codes that don't exist
        $extraUnits = ['GENG' => '根', 'HE' => '盒'];
        foreach ($extraUnits as $code => $name) {
            DepartmentCode::updateOrCreate(['type' => 'hc_unit', 'code' => $code], ['name' => $name]);
        }

        $consumableModels = [];
        foreach ($consumables as $c) {
            $consumableModels[] = Consumable::updateOrCreate(
                ['name' => $c['name']],
                $c
            );
        }

        // ========== 4. 入库单（模拟6月和7月的入库记录）==========
        $user = $admin;

        // 6月入库单1
        $order1 = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260601-001',
            'intake_date' => '2026-06-01',
            'supplier_code' => 'JD',
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'completed',
            'remarks' => '6月初集中采购',
        ]);
        $order1Items = [
            ['consumable_id' => $consumableModels[0]->id, 'quantity' => 100, 'unit_price' => 28.00],  // A4纸
            ['consumable_id' => $consumableModels[2]->id, 'quantity' => 200, 'unit_price' => 3.50],   // 黑色签字笔
            ['consumable_id' => $consumableModels[5]->id, 'quantity' => 50, 'unit_price' => 5.00],    // 订书钉
            ['consumable_id' => $consumableModels[6]->id, 'quantity' => 100, 'unit_price' => 4.00],   // 文件夹
            ['consumable_id' => $consumableModels[8]->id, 'quantity' => 20, 'unit_price' => 89.00],   // 墨盒黑
            ['consumable_id' => $consumableModels[14]->id, 'quantity' => 50, 'unit_price' => 12.00],  // 垃圾袋
            ['consumable_id' => $consumableModels[17]->id, 'quantity' => 20, 'unit_price' => 55.00],  // 抽纸
        ];
        foreach ($order1Items as $item) {
            ConsumableIntakeItem::create(array_merge($item, ['intake_order_id' => $order1->id]));
            Consumable::where('id', $item['consumable_id'])->increment('current_stock', $item['quantity']);
        }
        $this->logIntake($order1, $order1Items, $user);

        // 6月入库单2
        $order2 = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260615-001',
            'intake_date' => '2026-06-15',
            'supplier_code' => 'TB',
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'completed',
            'remarks' => '淘宝补货',
        ]);
        $order2Items = [
            ['consumable_id' => $consumableModels[15]->id, 'quantity' => 30, 'unit_price' => 15.00],  // 洗手液
            ['consumable_id' => $consumableModels[16]->id, 'quantity' => 10, 'unit_price' => 35.00],  // 消毒液
            ['consumable_id' => $consumableModels[18]->id, 'quantity' => 30, 'unit_price' => 18.00],  // 纸杯
            ['consumable_id' => $consumableModels[7]->id, 'quantity' => 50, 'unit_price' => 8.00],    // 档案盒
        ];
        foreach ($order2Items as $item) {
            ConsumableIntakeItem::create(array_merge($item, ['intake_order_id' => $order2->id]));
            Consumable::where('id', $item['consumable_id'])->increment('current_stock', $item['quantity']);
        }
        $this->logIntake($order2, $order2Items, $user);

        // 7月入库单
        $order3 = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260701-001',
            'intake_date' => '2026-07-01',
            'supplier_code' => 'JD',
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'completed',
            'remarks' => '7月采购',
        ]);
        $order3Items = [
            ['consumable_id' => $consumableModels[0]->id, 'quantity' => 50, 'unit_price' => 27.50],   // A4纸
            ['consumable_id' => $consumableModels[3]->id, 'quantity' => 80, 'unit_price' => 3.50],    // 红色签字笔
            ['consumable_id' => $consumableModels[9]->id, 'quantity' => 10, 'unit_price' => 120.00],  // 墨盒彩
            ['consumable_id' => $consumableModels[10]->id, 'quantity' => 30, 'unit_price' => 8.00],   // 网线
            ['consumable_id' => $consumableModels[11]->id, 'quantity' => 15, 'unit_price' => 35.00],  // 鼠标
            ['consumable_id' => $consumableModels[12]->id, 'quantity' => 10, 'unit_price' => 55.00],  // 键盘
            ['consumable_id' => $consumableModels[13]->id, 'quantity' => 20, 'unit_price' => 45.00],  // U盘
            ['consumable_id' => $consumableModels[19]->id, 'quantity' => 20, 'unit_price' => 65.00],  // 碳粉
            ['consumable_id' => $consumableModels[21]->id, 'quantity' => 30, 'unit_price' => 22.00],  // 口罩
            ['consumable_id' => $consumableModels[22]->id, 'quantity' => 10, 'unit_price' => 28.00],  // 手套
            ['consumable_id' => $consumableModels[23]->id, 'quantity' => 50, 'unit_price' => 6.00],   // 便利贴
            ['consumable_id' => $consumableModels[24]->id, 'quantity' => 30, 'unit_price' => 5.00],   // 胶带
        ];
        foreach ($order3Items as $item) {
            ConsumableIntakeItem::create(array_merge($item, ['intake_order_id' => $order3->id]));
            Consumable::where('id', $item['consumable_id'])->increment('current_stock', $item['quantity']);
        }
        $this->logIntake($order3, $order3Items, $user);

        // 草稿入库单（未完成）
        $order4 = ConsumableIntakeOrder::create([
            'order_no' => 'HC-RK-20260708-001',
            'intake_date' => '2026-07-08',
            'supplier_code' => 'HX',
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'draft',
            'remarks' => '待审批',
        ]);
        ConsumableIntakeItem::create(['intake_order_id' => $order4->id, 'consumable_id' => $consumableModels[1]->id, 'quantity' => 30, 'unit_price' => 45.00]);
        ConsumableIntakeItem::create(['intake_order_id' => $order4->id, 'consumable_id' => $consumableModels[20]->id, 'quantity' => 20, 'unit_price' => 25.00]);

        // ========== 5. 领用记录（6月和7月）==========
        $deptCodes = ['CW', 'RS', 'ZHS', 'SHS', 'LTS', 'JC', 'XX', 'BG'];
        $usageRecords = [
            // 6月领用
            ['consumable_id' => $consumableModels[0]->id, 'department_code' => 'BG', 'quantity' => 15, 'usage_date' => '2026-06-03', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'CW', 'quantity' => 20, 'usage_date' => '2026-06-03', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'RS', 'quantity' => 15, 'usage_date' => '2026-06-05', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[6]->id, 'department_code' => 'XX', 'quantity' => 20, 'usage_date' => '2026-06-05', 'reason' => '档案整理'],
            ['consumable_id' => $consumableModels[14]->id, 'department_code' => 'BG', 'quantity' => 10, 'usage_date' => '2026-06-10', 'reason' => '日常清洁'],
            ['consumable_id' => $consumableModels[17]->id, 'department_code' => 'BG', 'quantity' => 5, 'usage_date' => '2026-06-10', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'ZHS', 'quantity' => 25, 'usage_date' => '2026-06-12', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[8]->id, 'department_code' => 'BG', 'quantity' => 3, 'usage_date' => '2026-06-15', 'reason' => '打印机更换'],
            ['consumable_id' => $consumableModels[5]->id, 'department_code' => 'JC', 'quantity' => 10, 'usage_date' => '2026-06-18', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[15]->id, 'department_code' => 'BG', 'quantity' => 5, 'usage_date' => '2026-06-20', 'reason' => '补充洗手液'],
            ['consumable_id' => $consumableModels[18]->id, 'department_code' => 'BG', 'quantity' => 5, 'usage_date' => '2026-06-22', 'reason' => '会议室补充'],
            ['consumable_id' => $consumableModels[0]->id, 'department_code' => 'SHS', 'quantity' => 10, 'usage_date' => '2026-06-25', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'LTS', 'quantity' => 20, 'usage_date' => '2026-06-25', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[6]->id, 'department_code' => 'CW', 'quantity' => 15, 'usage_date' => '2026-06-28', 'reason' => '档案归档'],
            // 7月领用
            ['consumable_id' => $consumableModels[0]->id, 'department_code' => 'CW', 'quantity' => 10, 'usage_date' => '2026-07-01', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[0]->id, 'department_code' => 'RS', 'quantity' => 8, 'usage_date' => '2026-07-01', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'BG', 'quantity' => 30, 'usage_date' => '2026-07-02', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[3]->id, 'department_code' => 'CW', 'quantity' => 15, 'usage_date' => '2026-07-02', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[11]->id, 'department_code' => 'XX', 'quantity' => 5, 'usage_date' => '2026-07-03', 'reason' => '设备更换'],
            ['consumable_id' => $consumableModels[14]->id, 'department_code' => 'BG', 'quantity' => 8, 'usage_date' => '2026-07-03', 'reason' => '日常清洁'],
            ['consumable_id' => $consumableModels[23]->id, 'department_code' => 'SHS', 'quantity' => 10, 'usage_date' => '2026-07-04', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[24]->id, 'department_code' => 'JC', 'quantity' => 5, 'usage_date' => '2026-07-04', 'reason' => '档案封箱'],
            ['consumable_id' => $consumableModels[21]->id, 'department_code' => 'BG', 'quantity' => 5, 'usage_date' => '2026-07-05', 'reason' => '外出检查'],
            ['consumable_id' => $consumableModels[0]->id, 'department_code' => 'ZHS', 'quantity' => 12, 'usage_date' => '2026-07-05', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[2]->id, 'department_code' => 'LTS', 'quantity' => 15, 'usage_date' => '2026-07-07', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[17]->id, 'department_code' => 'CW', 'quantity' => 3, 'usage_date' => '2026-07-07', 'reason' => '日常办公需要'],
            ['consumable_id' => $consumableModels[16]->id, 'department_code' => 'BG', 'quantity' => 2, 'usage_date' => '2026-07-08', 'reason' => '定期消毒'],
        ];

        foreach ($usageRecords as $ur) {
            $consumable = Consumable::find($ur['consumable_id']);
            $oldStock = $consumable->current_stock;
            $consumable->decrement('current_stock', $ur['quantity']);

            $usage = ConsumableUsage::create(array_merge($ur, [
                'operator_id' => $user->id,
                'operator_name' => $user->name,
            ]));

            ConsumableLog::create([
                'consumable_id' => $consumable->id,
                'consumable_name' => $consumable->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => 'usage',
                'description' => "领用出库 -{$ur['quantity']}，库存 {$oldStock}→{$consumable->current_stock}，部门: {$ur['department_code']}，事由: {$ur['reason']}",
                'old_stock' => $oldStock,
                'new_stock' => $consumable->current_stock,
                'reference_type' => 'consumable_usage',
                'reference_id' => $usage->id,
                'created_at' => $ur['usage_date'],
            ]);
        }

        // ========== 6. 盘点单（已完成）==========
        $inv = ConsumableInventory::create([
            'inventory_no' => 'HC-PD-20260630-001',
            'inventory_date' => '2026-06-30',
            'operator_id' => $user->id,
            'operator_name' => $user->name,
            'status' => 'completed',
            'remarks' => '6月底例行盘点',
        ]);

        // 盘点大部分准确，少数有差异
        foreach ($consumableModels as $c) {
            $c->refresh();
            $actual = $c->current_stock;
            $diff = 0;
            $reason = null;
            // 模拟少量盘亏
            if ($c->name === '黑色签字笔') { $actual -= 3; $diff = -3; $reason = '部分笔丢失'; }
            if ($c->name === '垃圾袋') { $actual -= 2; $diff = -2; $reason = '使用中损耗'; }

            ConsumableInventoryItem::create([
                'inventory_id' => $inv->id,
                'consumable_id' => $c->id,
                'book_quantity' => $c->current_stock,
                'actual_quantity' => $actual,
                'difference' => $diff,
                'reason' => $reason,
                'adjusted' => true,
            ]);

            if ($diff !== 0) {
                $oldStock = $c->current_stock;
                $c->update(['current_stock' => $actual]);
                ConsumableLog::create([
                    'consumable_id' => $c->id,
                    'consumable_name' => $c->name,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'action' => 'inventory_adjust',
                    'description' => "盘点盘亏 {$diff}，库存 {$oldStock}→{$actual}，原因: {$reason}",
                    'old_stock' => $oldStock,
                    'new_stock' => $actual,
                    'reference_type' => 'consumable_inventory',
                    'reference_id' => $inv->id,
                    'created_at' => '2026-06-30',
                ]);
            }
        }

        $this->command->info('耗材演示数据创建完成！');
        $this->command->info('耗材种类: ' . Consumable::count());
        $this->command->info('入库单: ' . ConsumableIntakeOrder::count());
        $this->command->info('领用记录: ' . ConsumableUsage::count());
        $this->command->info('盘点单: ' . ConsumableInventory::count());
    }

    private function logIntake($order, $items, $user): void
    {
        foreach ($items as $item) {
            $consumable = Consumable::find($item['consumable_id']);
            ConsumableLog::create([
                'consumable_id' => $consumable->id,
                'consumable_name' => $consumable->name,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => 'intake_complete',
                'description' => "入库单 {$order->order_no} 完成，入库 +{$item['quantity']}",
                'old_stock' => $consumable->current_stock - $item['quantity'],
                'new_stock' => $consumable->current_stock,
                'reference_type' => 'consumable_intake_order',
                'reference_id' => $order->id,
                'created_at' => $order->intake_date,
            ]);
        }
    }
}
