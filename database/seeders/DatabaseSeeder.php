<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
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
