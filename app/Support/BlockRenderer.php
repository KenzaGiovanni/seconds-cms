<?php

namespace App\Support;

use Illuminate\Support\Facades\View;

/**
 * Renders a content's `blocks` json (ordered [{ type, data }]) to HTML by
 * mapping each block to a `theme::blocks.<type>` partial. Unknown or missing
 * types fall back to `theme::blocks.fallback` so a bad block never fatals a page.
 */
class BlockRenderer
{
    public function render(?array $blocks): string
    {
        if (empty($blocks)) {
            return '';
        }

        return collect($blocks)
            ->map(fn ($block) => $this->renderBlock((array) $block))
            ->implode("\n");
    }

    public function renderBlock(array $block): string
    {
        $type = $block['type'] ?? null;
        $data = $block['data'] ?? [];

        $view = $type ? "theme::blocks.{$type}" : null;

        if ($view && View::exists($view)) {
            return View::make($view, ['data' => $data])->render();
        }

        return View::make('theme::blocks.fallback', ['type' => $type, 'data' => $data])->render();
    }
}
