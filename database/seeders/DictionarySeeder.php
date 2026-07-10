<?php

namespace Database\Seeders;

use App\Models\DepartmentCode;
use Illuminate\Database\Seeder;

class DictionarySeeder extends Seeder
{
    /**
     * Seed all basic dictionary data: departments, categories, statuses,
     * and consumable-specific dictionaries (via ConsumableDictionarySeeder).
     *
     * All data uses updateOrCreate so this seeder is idempotent.
     */
    public function run(): void
    {
        // ====== 部门（34个） ======
        $departments = [
            ['code' => 'bgs',       'name' => '办公室'],
            ['code' => 'cchxwsk',   'name' => '财产和行为税科'],
            ['code' => 'cwglk',     'name' => '财务管理科'],
            ['code' => 'dcnsk',     'name' => '督查内审科'],
            ['code' => 'desws',     'name' => '第二税务所'],
            ['code' => 'dsisws',    'name' => '第四税务所'],
            ['code' => 'dssws',     'name' => '第三税务所'],
            ['code' => 'dysws',     'name' => '第一税务所'],
            ['code' => 'fzk',       'name' => '法制科'],
            ['code' => 'gjssglk',   'name' => '国际税收管理科'],
            ['code' => 'hwhlwsk',   'name' => '货物和劳务税科'],
            ['code' => 'jgdw',      'name' => '机关党委'],
            ['code' => 'jjz',       'name' => '纪检组'],
            ['code' => 'jld',       'name' => '局领导'],
            ['code' => 'kfxxl',     'name' => '库房（新兴路）'],
            ['code' => 'kfynl',     'name' => '库房（云南路）'],
            ['code' => 'khkpk',     'name' => '考核考评科'],
            ['code' => 'lgbk',      'name' => '老干部科'],
            ['code' => 'nsfwk',     'name' => '纳税服务科'],
            ['code' => 'nsfwzx',    'name' => '纳税服务中心'],
            ['code' => 'nssws',     'name' => '南市税务所'],
            ['code' => 'nymsws',    'name' => '南营门税务所'],
            ['code' => 'qycsws',    'name' => '劝业场税务所'],
            ['code' => 'rsjyk',     'name' => '人事教育科'],
            ['code' => 'sbxfhfsrsk','name' => '社会保险费和非税收入科'],
            ['code' => 'sdsk',      'name' => '所得税科'],
            ['code' => 'srhsk',     'name' => '收入核算科'],
            ['code' => 'ssfxglj',   'name' => '税收风险管理局'],
            ['code' => 'ssjjfxk',   'name' => '税收经济分析科'],
            ['code' => 'wddsws',    'name' => '五大道税务所'],
            ['code' => 'xblsws',    'name' => '小白楼税务所'],
            ['code' => 'xxzx',      'name' => '信息中心'],
            ['code' => 'znhdfwywzx','name' => '征纳互动服务运营中心'],
            ['code' => 'zsglk',     'name' => '征收管理科'],
        ];
        foreach ($departments as $d) {
            DepartmentCode::updateOrCreate(
                ['type' => 'department', 'code' => $d['code']],
                ['name' => $d['name']]
            );
        }

        // ====== 资产类别（10个） ======
        $categories = [
            ['code' => 'DTGN',   'name' => '台式计算机（国产）'],
            ['code' => 'DTFN',   'name' => '台式计算机（非国产）'],
            ['code' => 'GPY',    'name' => '高拍仪'],
            ['code' => 'MON',    'name' => '显示器'],
            ['code' => 'OTH',    'name' => '其他'],
            ['code' => 'PRT',    'name' => '打印机'],
            ['code' => 'ROU',    'name' => '路由器'],
            ['code' => 'SFZDKQ', 'name' => '身份证读卡器'],
            ['code' => 'SRV',    'name' => '服务器'],
            ['code' => 'SWT',    'name' => '交换机'],
        ];
        foreach ($categories as $c) {
            DepartmentCode::updateOrCreate(
                ['type' => 'category', 'code' => $c['code']],
                ['name' => $c['name']]
            );
        }

        // ====== 资产状态（6个） ======
        $statuses = [
            ['code' => 'ZY',  'name' => '在用'],
            ['code' => 'XZ',  'name' => '闲置'],
            ['code' => 'WX',  'name' => '维修'],
            ['code' => 'JIE', 'name' => '借用'],
            ['code' => 'DBF', 'name' => '待报废'],
            ['code' => 'BF',  'name' => '报废'],
        ];
        foreach ($statuses as $s) {
            DepartmentCode::updateOrCreate(
                ['type' => 'status', 'code' => $s['code']],
                ['name' => $s['name']]
            );
        }

        // ====== 耗材字典（分类、单位、供应商） ======
        $this->call(ConsumableDictionarySeeder::class);
    }
}
