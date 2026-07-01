<?php

namespace App\Livewire\Install;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\Theme;
use App\Models\User;
use App\Support\DefaultContent;
use App\Support\ThemeManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Install Seconds')]
class Installer extends Component
{
    public string $siteName = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public bool $done = false;

    public function mount(): void
    {
        if (User::exists()) {
            $this->redirect(route('login'));
        }
    }

    public function install(): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'same:passwordConfirmation'],
        ]);

        // Run pending migrations.
        Artisan::call('migrate', ['--force' => true]);

        // Seed roles + default settings.
        (new RolesAndPermissionsSeeder)->run();
        (new SettingsSeeder)->run();

        // Apply site name.
        Setting::set('site_name', $this->siteName);

        // Install + activate default theme.
        $manager = app(ThemeManager::class);
        $defaultPath = $manager->themesPath('default');

        if (is_dir($defaultPath)) {
            $theme = $manager->install($defaultPath);
            $manager->activate($theme);
        }

        // Seed a default static home page + set it as the front page.
        DefaultContent::ensureHomePage();

        // Create super-admin.
        $user = User::create([
            'name' => 'Admin',
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
        ]);

        $user->assignRole(Role::SuperAdmin->value);

        $this->done = true;
        $this->redirect(route('admin.dashboard'));
    }

    public function render(): View
    {
        return view('livewire.install.installer');
    }
}
