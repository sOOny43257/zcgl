<?php

namespace App\Models;

use App\Services\PrintService;
use Illuminate\Database\Eloquent\Model;

class PrintTemplate extends Model
{
    const ORIENTATIONS = ['portrait', 'landscape'];
    const PAGE_SIZES = ['A4', 'A5'];

    protected $fillable = [
        'module', 'name', 'orientation', 'page_size', 'config', 'is_active', 'updated_by',
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
        return PrintService::defaultConfig($module);
    }

    public static function createDefault(string $module): static
    {
        return static::create([
            'module' => $module,
            'name' => PrintService::moduleLabel($module) . '打印模板',
            'orientation' => in_array($module, ['intake', 'transfer', 'disposal', 'consumable_intake']) ? 'landscape' : 'portrait',
            'page_size' => 'A4',
            'config' => static::defaultConfig($module),
            'is_active' => true,
        ]);
    }
}
