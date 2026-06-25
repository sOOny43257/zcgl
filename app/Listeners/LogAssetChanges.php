<?php

namespace App\Listeners;

use App\Events\AssetChanged;
use App\Models\Asset;
use App\Models\AssetLog;

class LogAssetChanges
{
    public function handle(AssetChanged $event): void
    {
        $logs = [];
        $now = now();

        foreach (Asset::TRACKED_FIELDS as $field => $label) {
            if ($event->asset->isDirty($field)) {
                $logs[] = [
                    'asset_id' => $event->asset->id,
                    'user_id' => $event->userId,
                    'user_name' => $event->userName ?? '系统',
                    'field' => $field,
                    'field_label' => $label,
                    'old_value' => $event->original[$field] ?? null,
                    'new_value' => $event->asset->getAttribute($field),
                    'created_at' => $now,
                ];
            }
        }

        if (!empty($logs)) {
            AssetLog::insert($logs);
        }
    }
}
