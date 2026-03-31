<?php

use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\Tests\TestableImageBossBuilder;

function createSettings(array $overrides = []): Settings
{
    $settings = new Settings();
    $settings->source = $overrides['source'] ?? 'test-source';
    $settings->secret = $overrides['secret'] ?? null;
    $settings->baseUrl = $overrides['baseUrl'] ?? 'https://img.imageboss.me';
    $settings->useCloudSourcePath = $overrides['useCloudSourcePath'] ?? false;
    $settings->defaultWidth = $overrides['defaultWidth'] ?? 1000;
    $settings->defaultInterval = $overrides['defaultInterval'] ?? 320;
    $settings->presets = $overrides['presets'] ?? [
        'default' => ['min' => 320, 'max' => 2560],
        'thumbnail' => ['min' => 200, 'max' => 700, 'ratio' => 1, 'interval' => 250],
        'card' => ['min' => 300, 'max' => 800, 'ratio' => 0.8],
        'hero' => ['min' => 640, 'max' => 3840],
        'shareImage' => ['min' => 1200, 'max' => 1200, 'ratio' => 1200 / 630, 'format' => 'jpg'],
    ];

    return $settings;
}

function createMockAsset(
    bool $hasFocalPoint = false,
    ?array $focalPoint = null,
    ?int $width = null,
    ?int $height = null,
    string $path = 'images/test.jpg',
): Mockery\MockInterface {
    $asset = Mockery::mock(\craft\elements\Asset::class);
    $asset->path = $path;
    $asset->shouldReceive('getHasFocalPoint')->andReturn($hasFocalPoint);

    if ($hasFocalPoint && $focalPoint) {
        $asset->shouldReceive('getFocalPoint')->andReturn($focalPoint);
    } elseif (! $hasFocalPoint) {
        $asset->shouldReceive('getFocalPoint')->andReturn(null);
    }

    $asset->shouldReceive('getWidth')->andReturn($width);
    $asset->shouldReceive('getHeight')->andReturn($height);

    return $asset;
}

function createBuilder(
    ?Mockery\MockInterface $asset = null,
    ?Settings $settings = null,
): TestableImageBossBuilder {
    $asset ??= createMockAsset();
    $settings ??= createSettings();

    return new TestableImageBossBuilder($asset, $settings);
}
