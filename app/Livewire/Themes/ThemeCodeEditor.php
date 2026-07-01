<?php

namespace App\Livewire\Themes;

use App\Enums\Permission;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * In-admin theme code editor. Writing a Blade file is effectively remote code
 * execution, so this is guarded three ways:
 *   1. Hard off switch: config('seconds.theme_editor') (env SECONDS_THEME_EDITOR).
 *   2. Permission: themes.edit_code (developer / super-admin only).
 *   3. Path jail: every read/write is resolved with realpath and must stay
 *      inside base_path('themes'); only whitelisted extensions are touched.
 * Saves back up the previous version and are logged.
 */
#[Layout('layouts.admin')]
#[Title('Theme Code')]
class ThemeCodeEditor extends Component
{
    public ?string $currentFile = null;

    public string $content = '';

    public function mount(): void
    {
        abort_unless(SiteSettings::themeEditorEnabled(), 404);
        abort_unless(auth()->user()->can(Permission::ThemesEditCode->value), 403);
    }

    /** Absolute, symlink-resolved themes root. */
    private function root(): string
    {
        return realpath(base_path('themes')) ?: base_path('themes');
    }

    /**
     * Resolve a relative path to an absolute path that is guaranteed to live
     * inside the themes root and carry an allowed extension. Null = rejected.
     */
    private function safePath(string $relative): ?string
    {
        if (! $this->extensionAllowed($relative)) {
            return null;
        }

        $target = realpath($this->root().'/'.$relative);

        if ($target === false || ! is_file($target)) {
            return null;
        }

        $root = $this->root().DIRECTORY_SEPARATOR;

        return str_starts_with($target, $root) ? $target : null;
    }

    private function extensionAllowed(string $path): bool
    {
        foreach ((array) config('seconds.theme_editor_extensions', []) as $ext) {
            if (str_ends_with($path, '.'.$ext)) {
                return true;
            }
        }

        return false;
    }

    /** Flat list of editable files, relative to the themes root. */
    private function files(): array
    {
        $root = $this->root();

        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        foreach (File::allFiles($root) as $file) {
            $relative = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            if ($this->extensionAllowed($relative)) {
                $files[] = $relative;
            }
        }

        sort($files);

        return $files;
    }

    public function selectFile(string $relative): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesEditCode->value), 403);

        $target = $this->safePath($relative);

        if (! $target) {
            session()->flash('error', 'That file cannot be opened.');

            return;
        }

        $this->currentFile = $relative;
        $this->content = File::get($target);
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can(Permission::ThemesEditCode->value), 403);

        if (! $this->currentFile) {
            return;
        }

        $target = $this->safePath($this->currentFile);

        if (! $target) {
            session()->flash('error', 'That file cannot be saved.');

            return;
        }

        // Back up the previous version before overwriting.
        $backupDir = storage_path('app/theme-backups');
        File::ensureDirectoryExists($backupDir);
        $backupName = str_replace('/', '__', $this->currentFile).'.'.now()->format('Ymd_His').'.bak';
        File::copy($target, $backupDir.'/'.$backupName);

        File::put($target, $this->content);

        Log::info('Theme file edited', [
            'user_id' => auth()->id(),
            'file' => $this->currentFile,
        ]);

        session()->flash('success', 'Saved '.$this->currentFile.'.');
    }

    public function render()
    {
        return view('livewire.themes.theme-code-editor', [
            'files' => $this->files(),
        ]);
    }
}
