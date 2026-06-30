<?php

namespace App\Livewire\Themes;

use App\Enums\Permission;
use App\Models\Theme;
use App\Support\ThemeManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use ZipArchive;

#[Layout('layouts.admin')]
#[Title('Themes')]
class ThemeAdmin extends Component
{
    use WithFileUploads;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $zipFile = null;

    public ?int $confirmingUninstall = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);
    }

    public function install(): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);

        $this->validate([
            'zipFile' => 'required|file|mimes:zip|max:20480',
        ]);

        $manager = app(ThemeManager::class);

        // Extract ZIP to a temp dir then validate theme.json before committing.
        $tmpDir = sys_get_temp_dir() . '/seconds-theme-' . Str::random(8);
        mkdir($tmpDir, 0755, true);

        try {
            $zip = new ZipArchive;
            $result = $zip->open($this->zipFile->getRealPath());

            if ($result !== true) {
                throw new \RuntimeException('Could not open ZIP file.');
            }

            $zip->extractTo($tmpDir);
            $zip->close();

            // Theme folder may be at root or one level deep.
            $themePath = $this->findThemePath($tmpDir);

            if (! $themePath) {
                throw new \RuntimeException('No valid theme.json found in the ZIP.');
            }

            // Validate manifest before moving.
            $manifest = \App\Support\ThemeManifest::fromPath($themePath);

            // Move to themes directory.
            $dest = $manager->themesPath($manifest->slug);

            if (is_dir($dest)) {
                File::deleteDirectory($dest);
            }

            File::copyDirectory($themePath, $dest);

            $manager->install($dest);

            session()->flash('success', "Theme \"{$manifest->name}\" installed successfully.");
        } catch (\Throwable $e) {
            session()->flash('error', 'Install failed: ' . $e->getMessage());
        } finally {
            File::deleteDirectory($tmpDir);
        }

        $this->reset('zipFile');
    }

    public function activate(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);

        $theme = Theme::findOrFail($id);
        app(ThemeManager::class)->activate($theme);

        session()->flash('success', "\"{$theme->name}\" is now the active theme.");
    }

    public function confirmUninstall(int $id): void
    {
        $this->confirmingUninstall = $id;
    }

    public function cancelUninstall(): void
    {
        $this->confirmingUninstall = null;
    }

    public function uninstall(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesManage->value), 403);

        $theme = Theme::findOrFail($id);

        try {
            app(ThemeManager::class)->uninstall($theme);

            // Remove files from disk.
            $path = app(ThemeManager::class)->themesPath($theme->slug);
            if (is_dir($path)) {
                File::deleteDirectory($path);
            }

            $this->confirmingUninstall = null;
            session()->flash('success', "\"{$theme->name}\" uninstalled.");
        } catch (RuntimeException $e) {
            $this->confirmingUninstall = null;
            session()->flash('error', $e->getMessage());
        }
    }

    private function findThemePath(string $dir): ?string
    {
        // Check root first.
        if (file_exists($dir . '/theme.json')) {
            return $dir;
        }

        // One level deep.
        foreach (glob($dir . '/*/theme.json') as $path) {
            return dirname($path);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.themes.theme-admin', [
            'themes' => Theme::orderByRaw("status = 'active' DESC")->orderBy('name')->get(),
        ]);
    }
}
