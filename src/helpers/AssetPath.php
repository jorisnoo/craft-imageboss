<?php

namespace Noo\CraftImageboss\helpers;

use craft\elements\Asset;
use craft\helpers\App;
use Noo\CraftImageboss\models\Settings;

final class AssetPath
{
    /** The same source-relative path must be used for serving, signing and purging. */
    public static function for(Asset $asset, Settings $settings): string
    {
        $volume = $asset->getVolume();
        $parts = [];
        if ($settings->includeVolumeFolder) {
            $fs = $volume->getFs();
            if (property_exists($fs, 'path') && $fs->path) {
                $parts[] = basename(str_replace('\\', '/', (string) App::parseEnv($fs->path)));
            }
        }
        $parts[] = App::parseEnv($volume->getSubpath());
        $parts[] = $asset->getPath();

        $encoded = [];
        foreach (explode('/', str_replace('\\', '/', implode('/', $parts))) as $segment) {
            if ($segment !== '' && $segment !== '.' && $segment !== '..') {
                $encoded[] = rawurlencode($segment);
            }
        }

        return implode('/', $encoded);
    }
}
