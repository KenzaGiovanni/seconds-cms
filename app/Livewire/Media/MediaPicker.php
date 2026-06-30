<?php

namespace App\Livewire\Media;

use App\Models\Media;
use Livewire\Component;

/**
 * Inline media picker modal. Parent components listen for the 'media-selected'
 * event dispatched by this component (payload: { id, url }).
 */
class MediaPicker extends Component
{
    public bool $open = false;
    public ?int $selected = null;

    public function pick(int $id): void
    {
        $media = Media::findOrFail($id);
        $this->dispatch('media-selected', id: $media->id, url: $media->url());
        $this->open = false;
        $this->selected = null;
    }

    public function render()
    {
        return view('livewire.media.media-picker', [
            'mediaItems' => $this->open ? Media::latest()->get() : collect(),
        ]);
    }
}
