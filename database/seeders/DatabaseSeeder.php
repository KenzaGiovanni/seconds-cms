<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Theme;
use App\Models\User;
use App\Support\DefaultContent;
use App\Support\ThemeManager;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(SettingsSeeder::class);

        // Install + activate the default theme if not already present.
        if (! Theme::where('slug', 'default')->exists()) {
            $manager = app(ThemeManager::class);
            $theme = $manager->install(base_path('themes/default'));
            $manager->activate($theme);
        }

        // Ensure a default static home page exists and is set as the front page.
        DefaultContent::ensureHomePage();

        // Dev super-admin login.
        $admin = User::firstOrCreate(
            ['email' => 'admin@seconds.test'],
            [
                'name' => 'Seconds Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::SuperAdmin->value]);
    }
}
