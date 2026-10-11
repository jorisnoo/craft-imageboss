<?php

namespace Noo\CraftImageboss\helpers;

use craft\elements\Asset;
use craft\helpers\App;
use Noo\CraftImageboss\models\Settings;

/**
 * Builds the source-relative, URL-encoded path ImageBoss uses to locate an asset.
 *
 * Shared by transform URLs and cache purge URLs, which must address the exact same path.
 */
final class AssetPath
{
    public static function for(Asset $asset, Settings $settings): string
    {
        $volume = $asset->getVolume();
        $fs = $volume->getFs();

        $parts = [];

        if ($settings->includeVolumeFolder && property_exists($fs, 'path') && $fs->path !== '') {
            $parts[] = basename(trim(App::parseEnv($fs->path), '/'));
        }

        $subpath = $volume->getSubpath();
        if ($subpath !== '') {
            $parts[] = App::parseEnv($subpath);
        }

        $parts[] = $asset->getPath();

        return self::encode(implode('/', $parts));
    }

    /**
     * Normalises separators, drops empty, `.` and `..` segments, and percent-encodes each segment.
     */
    public static function encode(string $path): string
    {
        $segments = explode('/', str_replace('\\', '/', $path));

        $segments = array_filter(
            $segments,
            static fn (string $segment) => ! in_array($segment, ['', '.', '..'], true),
        );

        return implode('/', array_map('rawurlencode', $segments));
    }
}
