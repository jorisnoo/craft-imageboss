<?php

use craft\elements\Asset;
use Noo\CraftImageboss\Tests\TestablePurgeAssetFromImageBoss;

it('builds purge url for asset', function () {
    $asset = createMockAsset(path: 'images/photo.jpg');
    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/images/photo.jpg');
});

it('includes volume folder when configured', function () {
    $fs = Mockery::mock(\craft\base\FsInterface::class)->shouldIgnoreMissing();
    $fs->path = '/var/www/uploads';

    $volume = Mockery::mock(\craft\models\Volume::class)->shouldIgnoreMissing();
    $volume->shouldReceive('getFs')->andReturn($fs);
    $volume->shouldReceive('getSubpath')->andReturn('');

    $asset = Mockery::mock(Asset::class)->shouldIgnoreMissing();
    $asset->shouldReceive('getVolume')->andReturn($volume);
    $asset->shouldReceive('getPath')->andReturn('photo.jpg');

    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/uploads/photo.jpg');
});

it('excludes volume folder when disabled', function () {
    $fs = Mockery::mock(\craft\base\FsInterface::class)->shouldIgnoreMissing();
    $fs->path = '/var/www/uploads';

    $volume = Mockery::mock(\craft\models\Volume::class)->shouldIgnoreMissing();
    $volume->shouldReceive('getFs')->andReturn($fs);
    $volume->shouldReceive('getSubpath')->andReturn('');

    $asset = Mockery::mock(Asset::class)->shouldIgnoreMissing();
    $asset->shouldReceive('getVolume')->andReturn($volume);
    $asset->shouldReceive('getPath')->andReturn('photo.jpg');

    $listener = new TestablePurgeAssetFromImageBoss(createSettings(['includeVolumeFolder' => false]));

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/photo.jpg');
});

it('includes subpath in purge url', function () {
    $fs = Mockery::mock(\craft\base\FsInterface::class)->shouldIgnoreMissing();

    $volume = Mockery::mock(\craft\models\Volume::class)->shouldIgnoreMissing();
    $volume->shouldReceive('getFs')->andReturn($fs);
    $volume->shouldReceive('getSubpath')->andReturn('media/images');

    $asset = Mockery::mock(Asset::class)->shouldIgnoreMissing();
    $asset->shouldReceive('getVolume')->andReturn($volume);
    $asset->shouldReceive('getPath')->andReturn('photo.jpg');

    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/media/images/photo.jpg');
});

it('sanitizes backslashes in asset path', function () {
    $asset = createMockAsset(path: 'images\\photo.jpg');
    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/images/photo.jpg');
});

it('sanitizes double dots in asset path', function () {
    $asset = createMockAsset(path: 'images/../photo.jpg');
    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/images/photo.jpg');
});

it('collapses multiple slashes in asset path', function () {
    $asset = createMockAsset(path: 'images///photo.jpg');
    $listener = new TestablePurgeAssetFromImageBoss(createSettings());

    expect($listener->buildPurgeUrl($asset))
        ->toBe('https://img.imageboss.me/test-source/images/photo.jpg');
});
