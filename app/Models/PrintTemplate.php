<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    const ORIENTATIONS = ['portrait', 'landscape'];
    const PAGE_SIZES = ['A4', 'A5'];

    protected $fillable = [
        'module',
        'name',
        'orientation',
        'page_size',
        'config',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function forModule(string $module): ?static
    {
        return static::active()->where('module', $module)->first();
    }

    public function getConfig(string $section, mixed $default = null): mixed
    {
        return data_get($this->config, $section, $default);
    }

    public function orientationCss(): string
    {
        $size = in_array($this->page_size, static::PAGE_SIZES, true) ? $this->page_size : 'A4';
        $orientation = in_array($this->orientation, static::ORIENTATIONS, true) ? $this->orientation : 'portrait';

        return "{$size} {$orientation}";
    }

    public static function defaultConfig(string $module): array
    {
        return match ($module) {
            'intake' => [
                'page' => [
                    'title' => '资产入库单',
                    'order_no_prefix' => '单号：',
                    'meta' => ['入库日期', '供应商', '采购单号', '总金额', '经办人', '验收人'],
                ],
                'table' => [
                    'show_index' => true,
                    'show_total' => true,
                    'columns' => [
                        ['key' => 'asset_code', 'label' => '资产编号'],
                        ['key' => 'name', 'label' => '资产名称'],
                        ['key' => 'category', 'label' => '类别'],
                        ['key' => 'brand', 'label' => '品牌'],
                        ['key' => 'model', 'label' => '规格型号'],
                        ['key' => 'sn', 'label' => 'SN序列号'],
                        ['key' => 'department', 'label' => '部门'],
                        ['key' => 'room', 'label' => '房间号'],
                        ['key' => 'user', 'label' => '使用人'],
                        ['key' => 'purchase_price', 'label' => '单价'],
                    ],
                ],
                'signatures' => ['经办人', '验收人'],
            ],
            default => [],
        };
    }

    public static function createDefault(string $module): static
    {
        $names = [
            'intake' => '资产入库单打印模板',
        ];

        $orientations = [
            'intake' => 'landscape',
        ];

        return static::create([
            'module' => $module,
            'name' => $names[$module] ?? $module,
            'orientation' => $orientations[$module] ?? 'portrait',
            'page_size' => 'A4',
            'config' => static::defaultConfig($module),
            'is_active' => true,
        ]);
    }
}
