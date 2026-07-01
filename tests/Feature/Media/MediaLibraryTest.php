<?php

use App\Enums\Role;
use App\Livewire\Content\PageForm;
use App\Livewire\Media\MediaLibrary;
use App\Livewire\Media\MediaPicker;
use App\Models\Media;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Storage::fake('public');
});

// -- Access --

it('blocks guests from media library', function () {
    $this->get('/admin/media')->assertRedirect('/admin/login');
});

it('allows an editor to access media library', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/media')->assertOk();
});

// -- Upload --

it('uploads a file and stores a media row', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $file = UploadedFile::fake()->image('test-photo.jpg', 800, 600);

    Livewire::actingAs($user)
        ->test(MediaLibrary::class)
        ->set('upload', $file)
        ->set('altText', 'A test photo')
        ->call('store');

    expect(Media::count())->toBe(1);
    $media = Media::first();
    expect($media->filename)->toBe('test-photo.jpg')
        ->and($media->alt)->toBe('A test photo')
        ->and($media->mime_type)->toBe('image/jpeg')
        ->and($media->disk)->toBe('public');

    Storage::disk('public')->assertExists($media->path);
});

it('validates that upload is required', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(MediaLibrary::class)
        ->call('store')
        ->assertHasErrors(['upload']);
});

it('rejects files over 10 MB', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $file = UploadedFile::fake()->create('big.jpg', 11000, 'image/jpeg'); // 11 MB

    Livewire::actingAs($user)
        ->test(MediaLibrary::class)
        ->set('upload', $file)
        ->call('store')
        ->assertHasErrors(['upload']);
});

// -- Delete --

it('deletes a media row and its file', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $file = UploadedFile::fake()->image('to-delete.jpg');
    $path = $file->store('media', 'public');

    $media = Media::create([
        'disk' => 'public',
        'path' => $path,
        'filename' => 'to-delete.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'uploaded_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MediaLibrary::class)
        ->call('delete', $media->id);

    expect(Media::find($media->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

// -- MediaPicker dispatches event --

it('picker dispatches media-selected event when an item is clicked', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $media = Media::create([
        'disk' => 'public',
        'path' => 'media/test.jpg',
        'filename' => 'test.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 512,
        'uploaded_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(MediaPicker::class)
        ->set('open', true)
        ->call('pick', $media->id)
        ->assertDispatched('media-selected', id: $media->id);
});

// -- Featured image on content --

it('saves featured_image_id on a page', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $media = Media::create([
        'disk' => 'public',
        'path' => 'media/hero.jpg',
        'filename' => 'hero.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'uploaded_by' => $user->id,
    ]);

    $component = Livewire::actingAs($user)
        ->test(PageForm::class)
        ->set('title', 'With Image')
        ->set('slug', 'with-image')
        ->set('status', 'draft')
        ->set('featuredImageId', $media->id)
        ->call('save');

    $page = Page::where('slug', 'with-image')->first();
    expect($page->featured_image_id)->toBe($media->id);
});
