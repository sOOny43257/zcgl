<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Asset extends Model
{
    use HasFactory;

    const STATUSES = ['在用', '闲置', '维修', '借用', '待报废', '报废'];

    const CATEGORIES = ['台式计算机（国产）', '台式计算机（非国产）', '打印机', '交换机', '显示器', '服务器', '路由器', '其他'];

    const TRACKED_FIELDS = [
        'name' => '资产名称',
        'department' => '部门',
        'room' => '房间号',
        'ip' => 'IP地址',
        'mac' => 'MAC地址',
        'sn' => 'SN序列号',
        'brand' => '品牌',
        'model' => '规格型号',
        'category' => '类别',
        'status' => '状态',
        'user' => '使用人',
        'remarks' => '备注',
    ];

    protected $fillable = [
        'asset_code',
        'financial_code',
        'name',
        'department',
        'room',
        'ip',
        'mac',
        'sn',
        'brand',
        'model',
        'category',
        'status',
        'user',
        'remarks',
        'purchase_date',
        'purchase_price',
        'supplier',
        'warranty_date',
        'intake_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_date' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // 自动生成自有编号
        static::creating(function (Asset $asset) {
            if (empty($asset->asset_code)) {
                $asset->asset_code = self::generateAssetCode($asset->category);
            }
        });

        // 变更追踪 — 通过事件解耦 Auth 依赖
        static::updating(function (Asset $asset) {
            $hasChanges = false;
            foreach (self::TRACKED_FIELDS as $field => $label) {
                if ($asset->isDirty($field)) { $hasChanges = true; break; }
            }
            if ($hasChanges) {
                \App\Events\AssetChanged::dispatch(
                    $asset,
                    $asset->getOriginal(),
                    Auth::id(),
                    Auth::check() ? Auth::user()->name : '系统'
                );
            }
        });
    }

    public static function generateAssetCode($category): string
    {
        $prefix = match (true) {
            str_contains($category ?? '', '计算机') => 'C',
            ($category ?? '') === '打印机' => 'P',
            default => 'D',
        };

        $year = date('y');
        $last = self::where('asset_code', 'like', $prefix . $year . '%')
            ->orderByDesc('asset_code')->first();

        $num = $last ? ((int) substr($last->asset_code, 3)) + 1 : 1;

        return $prefix . $year . str_pad($num, 3, '0', STR_PAD_LEFT);
    }

    public function intake()
    {
        return $this->belongsTo(AssetIntake::class);
    }

    public function logs()
    {
        return $this->hasMany(AssetLog::class)->orderBy('created_at', 'desc');
    }

    // === 编码→中文名翻译（带缓存） ===
    protected static $nameCache = null;

    protected static function loadNameCache(): void
    {
        if (self::$nameCache === null) {
            self::$nameCache = \App\Models\DepartmentCode::all()
                ->groupBy('type')
                ->map(fn($g) => $g->pluck('name', 'code'))
                ->toArray();
        }
    }

    public static function translateDept($code): string
    {
        self::loadNameCache();
        return self::$nameCache['department'][$code] ?? $code ?? '-';
    }

    public static function translateCat($code): string
    {
        self::loadNameCache();
        return self::$nameCache['category'][$code] ?? $code ?? '-';
    }

    public static function translateStatus($code): string
    {
        self::loadNameCache();
        return self::$nameCache['status'][$code] ?? $code ?? '-';
    }

    public static function translateAll($asset): array
    {
        return [
            'department_name' => self::translateDept($asset->department),
            'category_name' => self::translateCat($asset->category),
            'status_name' => self::translateStatus($asset->status),
        ];
    }
}
