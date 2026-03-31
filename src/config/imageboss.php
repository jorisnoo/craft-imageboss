<?php

use craft\helpers\App;

return [
    /*
    |--------------------------------------------------------------------------
    | ImageBoss Source
    |--------------------------------------------------------------------------
    |
    | Your ImageBoss source identifier. When not set, the plugin falls back
    | to Craft's native image transforms for local development.
    |
    */
    'source' => App::env('IMAGEBOSS_SOURCE'),

    /*
    |--------------------------------------------------------------------------
    | ImageBoss Token
    |--------------------------------------------------------------------------
    |
    | Optional HMAC token for signing ImageBoss URLs. When set, all URLs
    | will be signed using SHA-256 to prevent URL tampering.
    |
    */
    'token' => App::env('IMAGEBOSS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    */
    'baseUrl' => 'https://img.imageboss.me',

    /*
    |--------------------------------------------------------------------------
    | Include Volume Folder
    |--------------------------------------------------------------------------
    |
    | When true, the last segment of the volume's filesystem path is included
    | in the ImageBoss URL. Enable this when your ImageBoss source maps to a
    | parent directory containing multiple volume folders.
    |
    */
    'includeVolumeFolder' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Width
    |--------------------------------------------------------------------------
    |
    | The default width when url() is called without an explicit width.
    |
    */
    'defaultWidth' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Default Interval
    |--------------------------------------------------------------------------
    |
    | The default step size (in pixels) when generating srcset width variants
    | between min and max. Individual presets can override this.
    |
    */
    'defaultInterval' => 200,

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Named presets for image transforms. Each preset defines a srcset range.
    |
    | Available options:
    | - min: Minimum width for srcset generation
    | - max: Maximum width for srcset generation
    | - ratio: Aspect ratio as width/height, e.g. 16/9
    | - interval: Width step size, overrides defaultInterval
    | - format: Output format override, e.g. 'jpg'
    |
    */
    'presets' => [],
];
