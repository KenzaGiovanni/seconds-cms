<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'site_name', 'value' => 'My Site', 'autoload' => true],
            ['key' => 'site_tagline', 'value' => '', 'autoload' => true],
            ['key' => 'site_email', 'value' => '', 'autoload' => true],
            ['key' => 'ecommerce', 'value' => 'false', 'autoload' => true],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], [
                'value' => $setting['value'],
                'autoload' => $setting['autoload'],
            ]);
        }

        Setting::flushCache();
    }
}
