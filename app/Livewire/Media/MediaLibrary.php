<?php

namespace App\Livewire\Media;

use App\Enums\Permission;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Media Library')]
class MediaLibrary extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $upload = null;

    public string $altText = '';

    public ?int $confirmingDelete = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);
    }

    public function store(): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $this->validate([
            'upload' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,pdf,mp4',
        ]);

        $filename = $this->upload->getClientOriginalName();
        $ext = $this->upload->getClientOriginalExtension();
        $slug = Str::slug(pathinfo($filename, PATHINFO_FILENAME));
        $uniqueName = $slug.'-'.Str::random(6).'.'.$ext;
        $path = $this->upload->storeAs('media', $uniqueName, 'public');

        [$width, $height] = $this->imageSize($path);

        Media::create([
            'disk' => 'public',
            'path' => $path,
            'filename' => $filename,
            'mime_type' => $this->upload->getMimeType(),
            'size' => $this->upload->getSize(),
            'alt' => $this->altText ?: null,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset('upload', 'altText');
        session()->flash('success', 'File uploaded.');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDelete = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can(Permission::ContentManage->value), 403);

        $media = Media::findOrFail($id);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        $this->confirmingDelete = null;
        session()->flash('success', 'File deleted.');
    }

    private function imageSize(string $path): array
    {
        $fullPath = Storage::disk('public')->path($path);
        if (function_exists('getimagesize') && @getimagesize($fullPath)) {
            [$w, $h] = getimagesize($fullPath);

            return [$w, $h];
        }

        return [null, null];
    }

    public function render()
    {
        return view('livewire.media.media-library', [
            'mediaItems' => Media::latest()->get(),
        ]);
    }
}
