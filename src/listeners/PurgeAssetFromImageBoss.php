<?php

namespace Noo\CraftImageboss\listeners;

use Craft;
use craft\elements\Asset;
use craft\events\ModelEvent;
use Noo\CraftImageboss\helpers\AssetPath;
use Noo\CraftImageboss\jobs\PurgeImageBossCacheJob;
use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\Plugin;
use WeakMap;
use yii\base\Event;

class PurgeAssetFromImageBoss
{
    /** @var WeakMap<Asset, string> */
    private WeakMap $previousUrls;

    public function __construct()
    {
        $this->previousUrls = new WeakMap();
    }

    public function beforeSave(ModelEvent $event): void
    {
        /** @var Asset $asset */
        $asset = $event->sender;
        unset($this->previousUrls[$asset]);
        // Craft resolves newFilename/newFolderId into newLocation before this event,
        // but getPath()/getVolume() still point at the old file until afterSave.
        if (! $event->isNew && ! $asset->propagating && ($asset->newLocation !== null || $asset->tempFilePath !== null)) {
            $this->previousUrls[$asset] = $this->buildPurgeUrl($asset);
        }
    }

    public function afterSave(ModelEvent $event): void
    {
        $asset = $event->sender;
        if (! isset($this->previousUrls[$asset])) {
            return;
        }

        $previous = $this->previousUrls[$asset];
        unset($this->previousUrls[$asset]);
        $current = $this->buildPurgeUrl($asset);
        $this->enqueue($previous);
        if ($current !== $previous) {
            $this->enqueue($current);
        }
    }

    public function afterDelete(Event $event): void
    {
        $this->enqueue($this->buildPurgeUrl($event->sender));
    }

    private function enqueue(string $url): void
    {
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
