<?php

namespace Noo\CraftImageboss\listeners;

use Craft;
use craft\elements\Asset;
use craft\events\ReplaceAssetEvent;
use craft\helpers\App;
use Noo\CraftImageboss\jobs\PurgeImageBossCacheJob;
use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\Plugin;

class PurgeAssetFromImageBoss
{
    public function handle(ReplaceAssetEvent $event): void
    {
        $url = $this->buildPurgeUrl($event->asset);

        Craft::$app->getQueue()->push(new PurgeImageBossCacheJob([
            'url' => $url,
        ]));
    }

    public function buildPurgeUrl(Asset $asset): string
    {
        $settings = $this->getSettings();

        $segments = ['', $settings->source];

        $this->appendAssetPath($segments, $asset);

        return $settings->baseUrl . implode('/', $segments);
    }

    private function appendAssetPath(array &$segments, Asset $asset): void
    {
        $volume = $asset->getVolume();
        $fs = $volume->getFs();

        $settings = $this->getSettings();

        if ($settings->includeVolumeFolder && property_exists($fs, 'path') && $fs->path !== '') {
            $fullPath = trim(App::parseEnv($fs->path), '/');
            $segments[] = basename($fullPath);
        }

        $subpath = $volume->getSubpath();
        if ($subpath !== '') {
            $segments[] = trim(App::parseEnv($subpath), '/');
        }

        $segments[] = $this->sanitizePath($asset->getPath());
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace(['\\', '..'], ['/', ''], $path);
        $path = (string) preg_replace('#/+#', '/', $path);

        return ltrim($path, '/');
    }

    protected function getSettings(): Settings
    {
        return Plugin::$plugin->getSettings();
    }
}
