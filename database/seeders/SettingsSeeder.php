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
            ['key' => 'timezone', 'value' => 'UTC', 'autoload' => true],
            ['key' => 'date_format', 'value' => 'd M Y', 'autoload' => true],
            ['key' => 'posts_per_page', 'value' => '10', 'autoload' => true],
            ['key' => 'show_on_front', 'value' => 'posts', 'autoload' => true],
            ['key' => 'front_page_id', 'value' => '', 'autoload' => true],
            ['key' => 'theme_editor_enabled', 'value' => 'false', 'autoload' => true],
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
