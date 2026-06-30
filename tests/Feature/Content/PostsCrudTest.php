<?php

use App\Enums\ContentStatus;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

// -- Access control --

it('blocks guests from posts admin', function () {
    $this->get('/admin/posts')->assertRedirect('/admin/login');
});

it('allows an editor (content.manage) to access posts list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Editor->value);

    actingAs($user)->get('/admin/posts')->assertOk();
});

// -- Create --

it('creates a post and redirects to the list', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'My First Post')
        ->set('slug', 'my-first-post')
        ->set('body', 'Hello world.')
        ->set('status', 'draft')
        ->call('save')
        ->assertRedirect(route('admin.posts.index'));

    expect(Post::where('slug', 'my-first-post')->exists())->toBeTrue();
});

it('auto-generates slug from title', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'Laravel Tips and Tricks')
        ->assertSet('slug', 'laravel-tips-and-tricks');
});

it('assigns categories to a post on save', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $cat = Category::create(['name' => 'Tech', 'slug' => 'tech']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'Tech Post')
        ->set('slug', 'tech-post')
        ->set('status', 'draft')
        ->set('selectedCategories', [$cat->id])
        ->call('save');

    $post = Post::where('slug', 'tech-post')->first();
    expect($post->categories->pluck('id')->contains($cat->id))->toBeTrue();
});

it('creates and assigns tags on save', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'Tagged Post')
        ->set('slug', 'tagged-post')
        ->set('status', 'draft')
        ->set('tagInput', 'laravel, php, web')
        ->call('save');

    $post = Post::where('slug', 'tagged-post')->first();
    expect($post->tags->pluck('name')->sort()->values()->all())
        ->toBe(['laravel', 'php', 'web']);
});

it('reuses an existing tag instead of creating a duplicate', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    Tag::create(['name' => 'laravel', 'slug' => 'laravel']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class)
        ->set('title', 'Another Post')
        ->set('slug', 'another-post')
        ->set('status', 'draft')
        ->set('tagInput', 'laravel')
        ->call('save');

    expect(Tag::where('slug', 'laravel')->count())->toBe(1);
});

// -- Edit --

it('loads an existing post for editing with its taxonomy', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $cat = Category::create(['name' => 'Design', 'slug' => 'design']);
    $post = Post::create(['title' => 'A Post', 'slug' => 'a-post', 'status' => ContentStatus::Draft]);
    $post->categories()->attach($cat->id);
    $post->tags()->sync(
        [Tag::create(['name' => 'ui', 'slug' => 'ui'])->id]
    );

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class, ['id' => $post->id])
        ->assertSet('title', 'A Post')
        ->assertSet('slug', 'a-post')
        ->assertSet('tagInput', 'ui');

    expect(collect(Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class, ['id' => $post->id])
        ->get('selectedCategories'))->contains($cat->id))->toBeTrue();
});

it('updates a post', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $post = Post::create(['title' => 'Old', 'slug' => 'old-post', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostForm::class, ['id' => $post->id])
        ->set('title', 'Updated')
        ->set('slug', 'updated-post')
        ->call('save')
        ->assertRedirect(route('admin.posts.index'));

    expect($post->fresh()->title)->toBe('Updated');
});

// -- Delete --

it('deletes a post', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::Admin->value);

    $post = Post::create(['title' => 'Delete Me', 'slug' => 'delete-post', 'status' => ContentStatus::Draft]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Content\PostList::class)
        ->call('delete', $post->id);

    expect(Post::find($post->id))->toBeNull();
});

// -- Front-end --

it('a published post renders at /blog/{slug}', function () {
    Post::create([
        'title' => 'Hello World',
        'slug' => 'hello-world',
        'status' => ContentStatus::Published,
        'published_at' => now()->subHour(),
        'body' => 'Welcome to my blog.',
    ]);

    get('/blog/hello-world')
        ->assertOk()
        ->assertSee('Hello World')
        ->assertSee('Welcome to my blog.');
});

it('blog index lists published posts', function () {
    Post::create(['title' => 'Post A', 'slug' => 'post-a', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    Post::create(['title' => 'Post B', 'slug' => 'post-b', 'status' => ContentStatus::Draft]);

    get('/blog')
        ->assertOk()
        ->assertSee('Post A')
        ->assertDontSee('Post B');
});

it('category archive lists only published posts in that category', function () {
    $cat = Category::create(['name' => 'News', 'slug' => 'news']);
    $other = Category::create(['name' => 'Other', 'slug' => 'other']);

    $inCat = Post::create(['title' => 'News Post', 'slug' => 'news-post', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $inCat->categories()->attach($cat->id);

    $draftInCat = Post::create(['title' => 'Draft News', 'slug' => 'draft-news', 'status' => ContentStatus::Draft]);
    $draftInCat->categories()->attach($cat->id);

    $notInCat = Post::create(['title' => 'Other Post', 'slug' => 'other-post', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $notInCat->categories()->attach($other->id);

    get('/category/news')
        ->assertOk()
        ->assertSee('News Post')
        ->assertDontSee('Draft News')
        ->assertDontSee('Other Post');
});

it('tag archive lists published posts with that tag', function () {
    $tag = Tag::create(['name' => 'tips', 'slug' => 'tips']);

    $withTag = Post::create(['title' => 'Tips Post', 'slug' => 'tips-post', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);
    $withTag->tags()->attach($tag->id);

    $without = Post::create(['title' => 'Other', 'slug' => 'other-tagged', 'status' => ContentStatus::Published, 'published_at' => now()->subHour()]);

    get('/tag/tips')
        ->assertOk()
        ->assertSee('Tips Post')
        ->assertDontSee('Other');
});

it('404s a draft post at /blog/{slug}', function () {
    Post::create(['title' => 'Secret', 'slug' => 'secret-post', 'status' => ContentStatus::Draft]);

    get('/blog/secret-post')->assertNotFound();
});
