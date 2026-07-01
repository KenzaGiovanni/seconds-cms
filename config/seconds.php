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

];
