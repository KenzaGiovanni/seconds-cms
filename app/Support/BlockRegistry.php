<?php

namespace App\Support;

use App\Models\Theme;

/**
 * Reads the active theme's block definitions from `themes/<slug>/blocks.php`
 * (a file returning `['type' => ['label' => ..., 'fields' => [...]], ...]`).
 *
 * The admin block editor builds its forms from these; the front-end renders
 * stored blocks through the matching `theme::blocks.<type>` partials. Results
 * are memoized per theme slug for the request. Registered as a singleton.
 */
class BlockRegistry
{
    /** @var array<string, array<string, BlockDefinition>> memo keyed by theme slug */
    private array $memo = [];

    public function __construct(private readonly ThemeManager $themes) {}

    /** @return array<string, BlockDefinition> keyed by block type */
    public function all(): array
    {
        $slug = Theme::active()?->slug ?? 'default';

        if (isset($this->memo[$slug])) {
            return $this->memo[$slug];
        }

        $file = $this->themes->themesPath($slug).'/blocks.php';
        $defs = [];

        if (is_file($file)) {
            $raw = require $file;

            if (is_array($raw)) {
                foreach ($raw as $type => $definition) {
                    if (is_array($definition)) {
                        $defs[$type] = BlockDefinition::fromArray($type, $definition);
                    }
                }
            }
        }

        return $this->memo[$slug] = $defs;
    }

    public function get(string $type): ?BlockDefinition
    {
        return $this->all()[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->all()[$type]);
    }

    /** [type => label] for the editor's "add block" picker. */
    public function options(): array
    {
        $options = [];
        foreach ($this->all() as $type => $def) {
            $options[$type] = $def->label;
        }

        return $options;
    }

    /** Drop the memo (e.g. after switching the active theme in a test). */
    public function forget(): void
    {
        $this->memo = [];
    }
}
