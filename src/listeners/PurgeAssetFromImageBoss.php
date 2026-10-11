<?php

namespace Noo\CraftImageboss\listeners;

use Craft;
use craft\elements\Asset;
use craft\events\ReplaceAssetEvent;
use Noo\CraftImageboss\helpers\AssetPath;
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

        return $settings->baseUrl . '/' . $settings->source . '/' . AssetPath::for($asset, $settings);
    }

    protected function getSettings(): Settings
    {
        return Plugin::$plugin->getSettings();
    }
}
