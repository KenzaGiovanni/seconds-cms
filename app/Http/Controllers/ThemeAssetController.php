<?php

namespace App\Http\Controllers;

use App\Support\ThemeManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves static files from a theme's `assets/` folder (css/js/images/fonts).
 * Path-jailed to `themes/<slug>/assets/` via realpath so a crafted `path`
 * cannot escape the theme (mirrors the theme code editor's safety model).
 */
class ThemeAssetController extends Controller
{
    /** Extensions we are willing to serve, mapped to their content type. */
    private const MIME = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'mjs' => 'application/javascript',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
    ];

    public function show(string $slug, string $path, ThemeManager $themes): BinaryFileResponse
    {
        abort_unless(preg_match('/^[a-z0-9][a-z0-9\-]*$/', $slug), 404);

        $assetsDir = realpath($themes->themesPath($slug).'/assets');
        abort_unless($assetsDir !== false, 404);

        $full = realpath($assetsDir.'/'.$path);
        abort_unless($full !== false, 404);

        // Jail: the resolved file must live inside the theme's assets folder.
        abort_unless(str_starts_with($full, $assetsDir.DIRECTORY_SEPARATOR), 404);

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        abort_unless(isset(self::MIME[$ext]), 404);

        return response()->file($full, [
            'Content-Type' => self::MIME[$ext],
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
