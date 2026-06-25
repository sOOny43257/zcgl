<?php

namespace App\Events;

use App\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;

class AssetChanged
{
    use Dispatchable;

    public function __construct(
        public Asset $asset,
        public array $original,
        public ?int $userId,
        public ?string $userName,
    ) {}
}
