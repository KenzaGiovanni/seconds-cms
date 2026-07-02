<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Theme code editor
    |--------------------------------------------------------------------------
    |
    | The in-admin theme code editor lets developer / super-admin roles read and
    | write theme template files from the browser. Because Blade compiles to PHP,
    | writing a template is effectively remote code execution - so it is OFF by
    | default and toggled on from the admin (Themes page), stored as the
    | `theme_editor_enabled` site setting. Even when on it is gated to the
    | `themes.edit_code` permission and jailed to the themes/ directory with the
    | extension whitelist below.
    |
    */

    // Extensions the code editor is allowed to open / save.
    'theme_editor_extensions' => ['blade.php', 'php', 'css', 'js', 'json', 'svg', 'txt', 'md'],

    /*
    |--------------------------------------------------------------------------
    | Low stock threshold
    |--------------------------------------------------------------------------
    |
    | A simple product or variant at or below this quantity is flagged as
    | "low stock" in the admin catalog and on the storefront. Only applies to
    | stock-tracking policies (deny / backorder) - StockPolicy::None never
    | shows a stock figure at all.
    |
    */

    'low_stock_threshold' => env('SECONDS_LOW_STOCK_THRESHOLD', 5),

    /*
    |--------------------------------------------------------------------------
    | Indonesia regions dataset
    |--------------------------------------------------------------------------
    |
    | Source for `regions:import-indonesia` (province -> regency -> district ->
    | village address picker). Official Kemendagri/BPS hierarchical codes,
    | via ibnux/data-indonesia's single-file mysqldump. One-time pull, not
    | fetched at request time - see seconds-spec.md for the full write-up.
    |
    */

    'indonesia_regions_source_url' => env(
        'INDONESIA_REGIONS_SOURCE_URL',
        'https://raw.githubusercontent.com/ibnux/data-indonesia/master/wilayah_indonesia.sql',
    ),

];
