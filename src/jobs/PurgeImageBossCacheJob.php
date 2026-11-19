<?php

namespace Noo\CraftImageboss\jobs;

use Craft;
use craft\queue\BaseJob;
use GuzzleHttp\Exception\ConnectException;
use Noo\CraftImageboss\Plugin;
use RuntimeException;
use yii\queue\RetryableJobInterface;

class PurgeImageBossCacheJob extends BaseJob implements RetryableJobInterface
{
    public string $url;

    public function execute($queue): void
    {
        $settings = Plugin::$plugin->getSettings();
        if (! $settings->apiKey || ! $settings->source || ! str_starts_with($this->url, $settings->baseUrl . '/' . $settings->source . '/')) {
            throw new RuntimeException('ImageBoss purge configuration no longer matches the queued asset.');
        }

        $client = Craft::createGuzzleClient(['timeout' => 10, 'connect_timeout' => 5]);

        $response = $client->delete($this->url, [
            'headers' => [
                'imageboss-api-key' => $settings->apiKey,
            ],
            'http_errors' => false,
            'allow_redirects' => false,
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("ImageBoss cache purge failed (HTTP {$status}).", $status);
        }
    }

    public function getTtr(): int
    {
        return 30;
    }

    public function canRetry($attempt, $error): bool
    {
        return $attempt < 3 && (
            $error instanceof ConnectException ||
            ($error instanceof RuntimeException && ($error->getCode() === 429 || ($error->getCode() >= 500 && $error->getCode() < 600)))
        );
    }

    protected function defaultDescription(): ?string
    {
        return "Purging ImageBoss cache for {$this->url}";
    }
}
