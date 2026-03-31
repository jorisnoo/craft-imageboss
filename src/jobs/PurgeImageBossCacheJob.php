<?php

namespace Noo\CraftImageboss\jobs;

use craft\queue\BaseJob;
use GuzzleHttp\Client;
use Noo\CraftImageboss\Plugin;

class PurgeImageBossCacheJob extends BaseJob
{
    public string $url;

    public function execute($queue): void
    {
        $apiKey = Plugin::$plugin->getSettings()->apiKey;

        $client = new Client();

        $response = $client->delete($this->url, [
            'headers' => [
                'imageboss-api-key' => $apiKey,
            ],
            'http_errors' => false,
        ]);

        if ($response->getStatusCode() >= 400) {
            \Craft::error(
                "ImageBoss cache purge failed for {$this->url} (HTTP {$response->getStatusCode()})",
                __METHOD__
            );
        }
    }

    protected function defaultDescription(): ?string
    {
        return "Purging ImageBoss cache for {$this->url}";
    }
}
