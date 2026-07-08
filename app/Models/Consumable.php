<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consumable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'category_code', 'spec', 'unit_code', 'supplier_code',
        'min_stock', 'current_stock', 'unit_price', 'remarks',
    ];

    protected $casts = [
        'min_stock' => 'integer',
        'current_stock' => 'integer',
        'unit_price' => 'decimal:2',
    ];

    // Relations
    public function intakeItems()
    {
        return $this->hasMany(ConsumableIntakeItem::class);
    }

    public function usages()
    {
        return $this->hasMany(ConsumableUsage::class);
    }

    public function logs()
    {
        return $this->hasMany(ConsumableLog::class, 'consumable_id')->orderByDesc('created_at');
    }

    // Dictionary translation helpers (reusing the same pattern as Asset)
    public function categoryName(): string
    {
        return DepartmentCode::where('type', 'hc_category')->where('code', $this->category_code)->value('name') ?? $this->category_code;
    }

    public function unitName(): string
    {
        return DepartmentCode::where('type', 'hc_unit')->where('code', $this->unit_code)->value('name') ?? $this->unit_code;
    }

    public function supplierName(): string
    {
        return $this->supplier_code
            ? (DepartmentCode::where('type', 'supplier')->where('code', $this->supplier_code)->value('name') ?? $this->supplier_code)
            : '-';
    }

    public function isLowStock(): bool
    {
        return $this->min_stock > 0 && $this->current_stock <= $this->min_stock;
    }

    // Scope for low-stock alerts
    public function scopeLowStock($query)
    {
        return $query->where('min_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'min_stock');
    }
}
