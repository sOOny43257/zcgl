<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessVoidOrder extends Model
{
    protected $fillable = [
        'order_no',
        'status',
        'source_doc_path',
        'source_file_name',
        'department',
        'flow_start_time',
        'company_name',
        'tax_no',
        'process_name',
        'termination_reason',
        'submitter_sign', 'department_chief_sign',
        'voided_by',
        'voided_at',
        'paper_submitted',
        'paper_submitted_at',
        'draft_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
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
        $prefix = 'LCZF-' . $today . '-';
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
            'flow_start_time' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:200'],
            'tax_no' => ['nullable', 'string', 'max:50'],
            'process_name' => ['nullable', 'string', 'max:2000'],
            'termination_reason' => ['nullable', 'string', 'max:2000'],
            'submitter_sign' => ['nullable', 'string', 'max:200'],
            'department_chief_sign' => ['nullable', 'string', 'max:200'],
        ];

        if ($strict) {
            $rules['department'] = ['required', 'string', 'max:100'];
            $rules['company_name'] = ['required', 'string', 'max:200'];
            $rules['tax_no'] = ['required', 'string', 'max:50'];
            $rules['process_name'] = ['required', 'string', 'max:2000'];
            $rules['termination_reason'] = ['required', 'string', 'max:2000'];
            $rules['voided_by'] = ['required', 'string', 'max:100'];
            $rules['voided_at'] = ['required', 'date'];
        }

        return $rules;
    }
}
