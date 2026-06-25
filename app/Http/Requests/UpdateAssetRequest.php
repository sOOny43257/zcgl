<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assetId = $this->route('asset')->id ?? $this->route('asset');

        return [
            'asset_code' => 'nullable|string|max:20|unique:assets,asset_code,' . $assetId,
            'financial_code' => 'nullable|string|max:50',
            'name' => 'nullable|string|max:200',
            'department' => 'nullable|string|max:100',
            'room' => 'nullable|string|max:50',
            'ip' => 'nullable|ip|max:45',
            'mac' => 'nullable|string|max:17|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/|unique:assets,mac,' . $assetId,
            'sn' => 'nullable|string|max:200',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'category' => 'required|string|max:50',
            'status' => 'required|string|max:20',
            'user' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ];
    }
}
