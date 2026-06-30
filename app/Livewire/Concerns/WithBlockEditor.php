<?php

namespace App\Livewire\Concerns;

use App\Support\BlockDefinition;
use App\Support\BlockRegistry;

/**
 * Schema-driven block editor for content forms. Reads the active theme's block
 * definitions (via {@see BlockRegistry}) and lets the editor add/reorder/remove
 * blocks and manage repeater rows. The host component owns `save()` and the
 * featured-image flow; it should call {@see applyBlockMedia()} from its
 * `media-selected` listener so block image fields can pick from the library.
 */
trait WithBlockEditor
{
    /** @var list<array{type: string, data: array<string, mixed>}> */
    public array $blocks = [];

    /** Block type selected in the "add block" picker. */
    public string $newBlockType = '';

    /** Dot-path (relative to $blocks) a media pick should fill, if any. */
    public ?string $mediaTargetPath = null;

    public function mountWithBlockEditor(): void
    {
        $this->newBlockType = (string) array_key_first($this->registry()->all());
    }

    public function addBlock(): void
    {
        $type = $this->newBlockType ?: (string) array_key_first($this->registry()->all());
        $def = $this->registry()->get($type);

        if (! $def) {
            return;
        }

        $this->blocks[] = ['type' => $type, 'data' => $def->defaultData()];
    }

    public function removeBlock(int $index): void
    {
        array_splice($this->blocks, $index, 1);
        $this->blocks = array_values($this->blocks);
    }

    public function moveBlockUp(int $index): void
    {
        if ($index <= 0 || ! isset($this->blocks[$index])) {
            return;
        }

        [$this->blocks[$index - 1], $this->blocks[$index]] = [$this->blocks[$index], $this->blocks[$index - 1]];
    }

    public function moveBlockDown(int $index): void
    {
        if ($index >= count($this->blocks) - 1) {
            return;
        }

        [$this->blocks[$index], $this->blocks[$index + 1]] = [$this->blocks[$index + 1], $this->blocks[$index]];
    }

    public function addRepeaterItem(int $blockIndex, string $fieldKey): void
    {
        $field = $this->repeaterField($blockIndex, $fieldKey);

        if (! $field) {
            return;
        }

        $this->blocks[$blockIndex]['data'][$fieldKey][] = $field->rowTemplate();
    }

    public function removeRepeaterItem(int $blockIndex, string $fieldKey, int $itemIndex): void
    {
        if (! isset($this->blocks[$blockIndex]['data'][$fieldKey][$itemIndex])) {
            return;
        }

        array_splice($this->blocks[$blockIndex]['data'][$fieldKey], $itemIndex, 1);
        $this->blocks[$blockIndex]['data'][$fieldKey] = array_values($this->blocks[$blockIndex]['data'][$fieldKey]);
    }

    /** Mark a block image field as the target for the next media pick. */
    public function chooseBlockMedia(string $path): void
    {
        $this->mediaTargetPath = $path;
    }

    /**
     * Route a media pick into the targeted block field. Returns true if it was
     * consumed (so the host can fall back to its featured-image handling).
     */
    protected function applyBlockMedia(int $id, string $url): bool
    {
        if (! $this->mediaTargetPath) {
            return false;
        }

        data_set($this->blocks, $this->mediaTargetPath, $url);
        $this->mediaTargetPath = null;

        return true;
    }

    /** @return array<string, BlockDefinition> */
    public function blockDefinitions(): array
    {
        return $this->registry()->all();
    }

    /** @return array<string, string> type => label, for the picker */
    public function blockOptions(): array
    {
        return $this->registry()->options();
    }

    private function repeaterField(int $blockIndex, string $fieldKey)
    {
        $type = $this->blocks[$blockIndex]['type'] ?? null;
        $field = $type ? $this->registry()->get($type)?->field($fieldKey) : null;

        return $field && $field->type === 'repeater' ? $field : null;
    }

    private function registry(): BlockRegistry
    {
        return app(BlockRegistry::class);
    }
}
