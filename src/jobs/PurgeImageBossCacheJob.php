<?php

namespace Noo\CraftImageboss\jobs;

use Craft;
use craft\queue\BaseJob;
use Noo\CraftImageboss\Plugin;

class PurgeImageBossCacheJob extends BaseJob
{
    public string $url;

    public function execute($queue): void
    {
        $apiKey = Plugin::$plugin->getSettings()->apiKey;

        // Craft's client picks up the project's Guzzle config (proxy, etc.); HTTP errors
        // throw, so the job is marked as failed and can be retried from the queue manager.
        Craft::createGuzzleClient(['timeout' => 30, 'connect_timeout' => 10])->delete($this->url, [
            'headers' => [
                'imageboss-api-key' => $apiKey,
            ],
        ]);
    }

    protected function defaultDescription(): ?string
    {
        return "Purging ImageBoss cache for {$this->url}";
    }
}
