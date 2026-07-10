<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissionOrder extends Model
{
    protected $fillable = [
        'order_no',
        'status',
        'source_doc_path',
        'source_file_name',
        'department',
        'fill_date',
        'items',
        'voided_by',
        'voided_at',
        'paper_submitted',
        'paper_submitted_at',
        'draft_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'items' => 'array',
        'draft_data' => 'array',
        'paper_submitted' => 'boolean',
        'voided_at' => 'datetime',
        'paper_submitted_at' => 'datetime',
    ];

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => '草稿',
            'voided' => '已作废',
            default => $this->status,
        };
    }

    public function paperSubmittedLabel(): string
    {
        return $this->paper_submitted ? '已提交' : '未提交';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function generateOrderNo(): string
    {
        $today = now()->format('Ymd');
        $prefix = 'QXD-' . $today . '-';
        $last = static::where('order_no', 'like', $prefix . '%')
            ->orderBy('order_no', 'desc')
            ->value('order_no');

        $count = $last ? (int) substr($last, -3) + 1 : 1;

        return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    public static function rules(bool $strict = false): array
    {
        $rules = [
            'department' => ['nullable', 'string', 'max:100'],
            'fill_date' => ['nullable', 'string', 'max:50'],
            'items' => ['nullable', 'array'],
            'items.*.names' => ['nullable', 'string', 'max:500'],
            'items.*.business_system' => ['nullable', 'string', 'max:200'],
            'items.*.original_position' => ['nullable', 'string', 'max:200'],
            'items.*.added_position' => ['nullable', 'string', 'max:2000'],
            'items.*.removed_position' => ['nullable', 'string', 'max:2000'],
        ];

        if ($strict) {
            $rules['department'] = ['required', 'string', 'max:100'];
            $rules['items'] = ['nullable', 'array'];
            $rules['voided_by'] = ['required', 'string', 'max:100'];
            $rules['voided_at'] = ['required', 'date'];
        }

        return $rules;
    }
}
