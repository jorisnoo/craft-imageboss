<?php

use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\web\twig\variables\CraftVariable;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Noo\CraftImageboss\jobs\PurgeImageBossCacheJob;
use Noo\CraftImageboss\Plugin;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use yii\base\Event;

beforeEach(function () {
    Craft::$app = Mockery::mock();
    $this->twig = new Environment(new ArrayLoader());
    $view = Mockery::mock();
    $view->shouldReceive('registerTwigExtension')->andReturnUsing($this->twig->addExtension(...));
    Craft::$app->shouldReceive('getView')->andReturn($view);

    $plugin = (new ReflectionClass(Plugin::class))->newInstanceWithoutConstructor();
    $plugin->getSettings()->source = 'photos';
    $plugin->getSettings()->apiKey = 'unit-test';
    $plugin->getSettings()->presets = ['square' => ['ratio' => 1]];
    $plugin->init();
});

afterEach(function () {
    Event::offAll();
    Craft::$app = null;
    Mockery::close();
});

it('registers the Craft variable and renders Twig filters without a site request', function () {
    $craft = (new ReflectionClass(CraftVariable::class))->newInstanceWithoutConstructor();
    Event::trigger($craft, CraftVariable::EVENT_INIT);
    $asset = createMockAsset();
    $options = ['preset' => 'square', 'width' => 800, 'quality' => 77, 'format' => 'webp', 'animation' => true, 'download' => true];
    $result = $craft->get('imageboss')->transform($asset, $options + ['min' => 400, 'max' => 800, 'interval' => 200]);
    expect(array_map(fn ($item) => $item->width, $result->all()))->toBe([400, 600, 800]);

    $render = fn ($template, $options) => $this->twig->createTemplate($template)->render(['asset' => $asset, 'options' => $options]);
    expect($render('{{ asset|imageboss_url(options) }}', $options))->toContain('/cover/800x800/', 'format:webp,quality:77,animation:true,download:1')
        ->and($render('{{ asset|imageboss_srcset(options) }}', ['width' => 400, 'height' => 300]))->toContain('/cover/400x300/', ' 400w')
        ->and($render('{{ asset|imageboss_cdn(options) }}', ['compression' => false]))->toContain('/cdn/compression:false/')
        ->and(rawurldecode($render('{{ asset|imageboss_placeholder(options) }}', $options + ['color' => 'red'])))->toContain("background:red");
});

it('queues only the affected master URLs on successful asset events', function () {
    $jobs = [];
    $queue = Mockery::mock();
    $queue->shouldReceive('push')->andReturnUsing(function (PurgeImageBossCacheJob $job) use (&$jobs) {
        $jobs[] = $job->url;
        return count($jobs);
    });
    Craft::$app->shouldReceive('getQueue')->andReturn($queue);

    $path = 'old.jpg';
    $asset = Mockery::mock(Asset::class);
    $asset->shouldReceive('getVolume')->andReturn(createMockAsset()->getVolume());
    $asset->shouldReceive('getPath')->andReturnUsing(function () use (&$path) {
        return $path;
    });
    $save = fn ($type) => Event::trigger($asset, $type, new ModelEvent());
    $save(Asset::EVENT_BEFORE_SAVE);
    $save(Asset::EVENT_AFTER_SAVE);
    expect($jobs)->toBe([]);

    $asset->tempFilePath = 'replacement';
    $save(Asset::EVENT_BEFORE_SAVE);
    $save(Asset::EVENT_AFTER_SAVE);
    expect($jobs)->toBe(['https://img.imageboss.me/photos/old.jpg']);

    $asset->tempFilePath = null;
    $asset->newLocation = '{folder:2}new.jpg';
    $save(Asset::EVENT_BEFORE_SAVE);
    // Craft relocates the file between the two events.
    $path = 'new folder/new.jpg';
    $asset->newLocation = null;
    $save(Asset::EVENT_AFTER_SAVE);
    Event::trigger($asset, Asset::EVENT_AFTER_DELETE);
    expect(array_slice($jobs, 1))->toBe([
        'https://img.imageboss.me/photos/old.jpg',
        'https://img.imageboss.me/photos/new%20folder/new.jpg',
        'https://img.imageboss.me/photos/new%20folder/new.jpg',
    ]);
});

it('uses Craft HTTP settings and distinguishes retryable purge outcomes', function (int $status, bool $retryable) {
    $url = 'https://img.imageboss.me/photos/master.jpg';
    $handler = new MockHandler([$status ? new Response($status) : new ConnectException('Timed out', new Request('DELETE', $url))]);
    $config = Mockery::mock();
    $config->shouldReceive('getConfigFromFile')->with('guzzle')->andReturn(['handler' => HandlerStack::create($handler)]);
    $config->shouldReceive('getGeneral')->andReturn((object) ['httpProxy' => 'http://proxy.invalid']);
    Craft::$app->shouldReceive('getVersion')->andReturn('5');
    Craft::$app->shouldReceive('getConfig')->andReturn($config);

    $job = new PurgeImageBossCacheJob(['url' => $url]);
    $error = null;
    try {
        $job->execute(null);
    } catch (RuntimeException $e) {
        $error = $e;
    }
    expect($error === null)->toBe($status === 204);
    if ($error !== null) {
        expect($job->canRetry(1, $error))->toBe($retryable)
            ->and($job->canRetry(3, $error))->toBeFalse();
    }
    expect($handler->getLastRequest()->getMethod())->toBe('DELETE')
        ->and($handler->getLastRequest()->getHeaderLine('imageboss-api-key'))->toBe('unit-test')
        ->and($handler->getLastOptions())->toMatchArray(['timeout' => 10, 'connect_timeout' => 5, 'proxy' => 'http://proxy.invalid', 'allow_redirects' => false]);
})->with([[204, false], [302, false], [403, false], [429, true], [503, true], [0, true]]);
