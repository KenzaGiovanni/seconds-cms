<?php

use App\Models\Theme;
use App\Support\ThemeManager;

beforeEach(function () {
    $manager = app(ThemeManager::class);
    if (! Theme::where('slug', 'default')->exists()) {
        $manager->install(base_path('themes/default'));
    }
    Theme::where('slug', 'default')->update(['status' => 'active']);
});

it('serves the default theme stylesheet', function () {
    $response = $this->get('/themes/default/assets/css/style.css');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/css');
    expect($response->streamedContent())->toContain(':root');
});

it('404s for a missing asset', function () {
    $this->get('/themes/default/assets/css/does-not-exist.css')->assertNotFound();
});

it('404s for an unknown theme', function () {
    $this->get('/themes/ghost-theme/assets/css/style.css')->assertNotFound();
});

it('refuses to serve files outside the assets folder (path jail)', function () {
    // blocks.php lives in the theme root, not under assets/ - the realpath jail must reject it.
    $this->get('/themes/default/assets/..%2Fblocks.php')->assertNotFound();
    $this->get('/themes/default/assets/../theme.json')->assertNotFound();
});

it('refuses to serve a non-whitelisted extension', function () {
    // Even a real file under assets/ with a disallowed extension is rejected.
    file_put_contents(base_path('themes/default/assets/secret.php'), '<?php echo "x";');

    try {
        $this->get('/themes/default/assets/secret.php')->assertNotFound();
    } finally {
        @unlink(base_path('themes/default/assets/secret.php'));
    }
});

it('builds a cache-busted asset url', function () {
    $url = app(ThemeManager::class)->assetUrl('css/style.css');

    expect($url)->toContain('/themes/default/assets/css/style.css')
        ->and($url)->toContain('?v=');
});

it('links the stylesheet from the theme layout', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('/themes/default/assets/css/style.css', false);
});
