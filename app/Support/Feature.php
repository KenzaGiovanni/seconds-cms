<?php

namespace App\Support;

use App\Models\Setting;

class Feature
{
    public static function ecommerce(): bool
    {
        return filter_var(Setting::get('ecommerce', 'false'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function enabled(string $key): bool
    {
        return filter_var(Setting::get($key, 'false'), FILTER_VALIDATE_BOOLEAN);
    }
}
