<?php

use Noo\CraftImageboss\builders\NullImageBossBuilder;
use Noo\CraftImageboss\builders\TransformResult;
use Noo\CraftImageboss\builders\TransformResultItem;
use Noo\CraftImageboss\Tests\Fixtures\InterfacePreset;
use Noo\CraftImageboss\Tests\Fixtures\TestPreset;

// --- URL generation ---

it('generates imageboss url with width operation', function () {
    $builder = createBuilder()->width(800);

    $url = $builder->url();

    expect($url)->toContain('https://img.imageboss.me')
        ->and($url)->toContain('test-source')
        ->and($url)->toContain('width/800')
        ->and($url)->toContain('format:auto');
});

it('generates imageboss url with cover operation when height is set', function () {
    $builder = createBuilder()->width(800)->height(600);

    $url = $builder->url();

    expect($url)->toContain('cover/800x600');
});

it('calculates height from ratio', function () {
    $builder = createBuilder()->width(800)->ratio(16 / 9);

    $url = $builder->url();

    expect($url)->toContain('cover/800x450');
});

it('uses default width from config when no width specified', function () {
    $settings = createSettings(['defaultWidth' => 500]);
    $builder = createBuilder(settings: $settings);

    $url = $builder->url();

    expect($url)->toContain('width/500');
});

it('includes format override in url', function () {
    $builder = createBuilder()->width(800)->format('jpg');

    $url = $builder->url();

    expect($url)->toContain('format:jpg')
        ->and($url)->not->toContain('format:auto');
});

// --- Focal points ---

it('includes focal point in url', function () {
    $asset = createMockAsset(hasFocalPoint: true, focalPoint: ['x' => 0.25, 'y' => 0.75]);

    $builder = createBuilder($asset)->width(800);

    $url = $builder->url();

    expect($url)->toContain('fp-x:0.25,fp-y:0.75');
});

it('excludes focal point when asset has none', function () {
    $asset = createMockAsset(hasFocalPoint: false);

    $builder = createBuilder($asset)->width(800);

    $url = $builder->url();

    expect($url)->not->toContain('fp-x')
        ->and($url)->not->toContain('fp-y');
});

// --- Signing ---

it('signs url when secret is configured', function () {
    $settings = createSettings(['secret' => 'test-secret']);
    $builder = createBuilder(settings: $settings)->width(800);

    $url = $builder->url();

    expect($url)->toContain('?bossToken=');
});

it('does not sign url when no secret', function () {
    $builder = createBuilder()->width(800);

    $url = $builder->url();

    expect($url)->not->toContain('bossToken');
});

it('generates correct hmac signature', function () {
    $settings = createSettings(['secret' => 'test-secret']);
    $builder = createBuilder(settings: $settings)->width(800);

    $url = $builder->url();

    // Extract the path and verify the token
    $parsedUrl = parse_url($url);
    $path = $parsedUrl['path'];
    parse_str($parsedUrl['query'] ?? '', $query);

    $expectedToken = hash_hmac('sha256', $path, 'test-secret');

    expect($query['bossToken'])->toBe($expectedToken);
});

// --- Presets ---

it('loads preset configuration', function () {
    $builder = createBuilder()->preset('card');

    $srcset = $builder->srcset();

    expect($srcset)->toBeArray()
        ->and($srcset[0]['width'])->toBe(300)
        ->and(end($srcset)['width'])->toBe(800);
});

it('applies ratio from preset', function () {
    $builder = createBuilder()->preset('thumbnail')->width(400);

    $url = $builder->url();

    expect($url)->toContain('cover/400x400');
});

it('applies interval from preset', function () {
    $builder = createBuilder()->preset('thumbnail');

    $srcset = $builder->srcset();
    $widths = array_column($srcset, 'width');

    expect($widths)->toBe([200, 450, 700]);
});

it('applies format from preset', function () {
    $builder = createBuilder()->preset('shareImage');

    $url = $builder->url();

    expect($url)->toContain('format:jpg')
        ->and($url)->not->toContain('format:auto');
});

it('ignores unknown preset', function () {
    $builder = createBuilder()->preset('nonexistent')->width(800);

    $url = $builder->url();

    expect($url)->toContain('width/800');
});

it('accepts backed enum for preset selection', function () {
    $builder = createBuilder()->preset(TestPreset::Card);

    $srcset = $builder->srcset();

    expect($srcset)->toBeArray()
        ->and($srcset[0]['width'])->toBe(300)
        ->and(end($srcset)['width'])->toBe(800);
});

it('accepts interface-based preset without config lookup', function () {
    $builder = createBuilder()->preset(InterfacePreset::Custom);

    $srcset = $builder->srcset();

    expect($srcset)->toBeArray()
        ->and($srcset[0]['width'])->toBe(100)
        ->and(end($srcset)['width'])->toBe(500);
});

it('applies ratio from interface-based preset', function () {
    $builder = createBuilder()->preset(InterfacePreset::WithRatio)->width(400);

    $url = $builder->url();

    expect($url)->toContain('cover/400x200');
});

it('applies interval from interface-based preset', function () {
    $builder = createBuilder()->preset(InterfacePreset::WithInterval);

    $srcset = $builder->srcset();
    $widths = array_column($srcset, 'width');

    expect($widths)->toBe([150, 300, 450]);
});

it('interface preset takes precedence over config lookup', function () {
    $settings = createSettings(['presets' => ['custom' => ['min' => 999, 'max' => 9999]]]);
    $builder = createBuilder(settings: $settings)->preset(InterfacePreset::Custom);

    $srcset = $builder->srcset();

    expect($srcset[0]['width'])->toBe(100)
        ->and(end($srcset)['width'])->toBe(500);
});

// --- Width generation ---

it('generates correct widths with default interval', function () {
    $builder = createBuilder()->min(300)->max(900);

    $srcset = $builder->srcset();
    $widths = array_column($srcset, 'width');

    expect($widths)->toBe([300, 620, 900]);
});

it('generates correct widths with custom interval', function () {
    $builder = createBuilder()->min(200)->max(600)->interval(200);

    $srcset = $builder->srcset();
    $widths = array_column($srcset, 'width');

    expect($widths)->toBe([200, 400, 600]);
});

it('always includes max width in srcset', function () {
    $builder = createBuilder()->min(300)->max(700)->interval(200);

    $srcset = $builder->srcset();
    $widths = array_column($srcset, 'width');

    expect($widths)->toBe([300, 500, 700]);
});

// --- Srcset string ---

it('generates srcset string format', function () {
    $builder = createBuilder()->min(300)->max(500)->interval(200);

    $srcsetString = $builder->srcsetString();

    expect($srcsetString)->toContain('300w')
        ->and($srcsetString)->toContain('500w')
        ->and($srcsetString)->toContain(', ');
});

// --- Aspect ratio ---

it('returns explicit ratio from aspectRatio()', function () {
    $builder = createBuilder()->ratio(16 / 9);

    expect($builder->aspectRatio())->toBe(16 / 9);
});

it('calculates aspectRatio() from width and height', function () {
    $builder = createBuilder()->width(800)->height(600);

    expect($builder->aspectRatio())->toBe(800 / 600);
});

it('returns null from aspectRatio() when insufficient data', function () {
    $builder = createBuilder();

    expect($builder->aspectRatio())->toBeNull();

    $builderWithWidth = createBuilder()->width(800);

    expect($builderWithWidth->aspectRatio())->toBeNull();
});

it('prefers explicit ratio over calculated ratio in aspectRatio()', function () {
    $builder = createBuilder()->width(800)->height(600)->ratio(16 / 9);

    expect($builder->aspectRatio())->toBe(16 / 9);
});

// --- Placeholder ---

it('generates placeholder with explicit width and height', function () {
    $placeholder = createBuilder()->width(800)->height(600)->placeholder();

    expect($placeholder)->toBe("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='600'%3E%3C/svg%3E");
});

it('generates placeholder with width and ratio', function () {
    $placeholder = createBuilder()->width(800)->ratio(16 / 9)->placeholder();

    expect($placeholder)->toBe("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='450'%3E%3C/svg%3E");
});

it('generates placeholder from asset native dimensions', function () {
    $asset = createMockAsset(width: 1920, height: 1080);

    $placeholder = createBuilder($asset)->placeholder();

    expect($placeholder)->toBe("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1920' height='1080'%3E%3C/svg%3E");
});

it('generates placeholder from preset min width and ratio', function () {
    $placeholder = createBuilder()->preset('card')->placeholder();

    expect($placeholder)->toStartWith('data:image/svg+xml,')
        ->and($placeholder)->toContain("width='300'");
});

it('returns empty string for placeholder when dimensions are unresolvable', function () {
    $asset = createMockAsset();

    $placeholder = createBuilder($asset)->placeholder();

    expect($placeholder)->toBe('');
});

it('generates placeholder starting with data uri prefix', function () {
    $asset = createMockAsset(width: 400, height: 300);

    $placeholder = createBuilder($asset)->placeholder();

    expect($placeholder)->toStartWith('data:image/svg+xml,');
});

// --- Null builder ---

it('returns empty values from null builder', function () {
    $builder = new NullImageBossBuilder();

    expect($builder->url())->toBe('')
        ->and($builder->srcset())->toBe([])
        ->and($builder->srcsetString())->toBe('')
        ->and($builder->placeholder())->toBe('')
        ->and($builder->aspectRatio())->toBeNull();
});

it('allows chaining on null builder', function () {
    $builder = new NullImageBossBuilder();

    $result = $builder->width(800)->height(600)->ratio(16 / 9)->min(320)->max(2560)->interval(320)->url();

    expect($result)->toBe('');
});

it('returns empty string for placeholder from null builder', function () {
    $builder = new NullImageBossBuilder();

    expect($builder->placeholder())->toBe('');
});

// --- Null safety ---

it('ignores null values passed to setter methods', function () {
    $builder = createBuilder()
        ->width(800)
        ->height(null)
        ->ratio(null)
        ->min(300)
        ->max(null)
        ->interval(null)
        ->format(null);

    $url = $builder->url();

    expect($url)->toContain('width/800')
        ->and($url)->not->toContain('cover/');

    $srcset = $builder->srcset();

    expect($srcset[0]['width'])->toBe(300);
});

// --- Trait helpers ---

it('provides helper methods via trait', function () {
    expect(InterfacePreset::Custom->min())->toBe(100)
        ->and(InterfacePreset::Custom->max())->toBe(500)
        ->and(InterfacePreset::Custom->ratio())->toBeNull()
        ->and(InterfacePreset::Custom->interval())->toBeNull()
        ->and(InterfacePreset::WithRatio->ratio())->toBe(2.0)
        ->and(InterfacePreset::WithInterval->interval())->toBe(150);
});

// --- Fallback mode ---

it('falls back to craft transform url when no source configured', function () {
    $settings = createSettings(['source' => null]);
    $asset = createMockAsset();
    $asset->shouldReceive('getUrl')->with(['width' => 800])->andReturn('/transforms/test_800.jpg');

    $builder = createBuilder($asset, $settings)->width(800);

    $url = $builder->url();

    expect($url)->toBe('/transforms/test_800.jpg');
});

it('passes height and crop mode in fallback', function () {
    $settings = createSettings(['source' => null]);
    $asset = createMockAsset();
    $asset->shouldReceive('getUrl')
        ->with(['width' => 800, 'height' => 450, 'mode' => 'crop'])
        ->andReturn('/transforms/test_800x450.jpg');

    $builder = createBuilder($asset, $settings)->width(800)->ratio(16 / 9);

    $url = $builder->url();

    expect($url)->toBe('/transforms/test_800x450.jpg');
});

it('passes format in fallback', function () {
    $settings = createSettings(['source' => null]);
    $asset = createMockAsset();
    $asset->shouldReceive('getUrl')
        ->with(['width' => 800, 'format' => 'webp'])
        ->andReturn('/transforms/test_800.webp');

    $builder = createBuilder($asset, $settings)->width(800)->format('webp');

    $url = $builder->url();

    expect($url)->toBe('/transforms/test_800.webp');
});

it('generates srcset in fallback mode', function () {
    $settings = createSettings(['source' => null]);
    $asset = createMockAsset();
    $asset->shouldReceive('getUrl')->andReturnUsing(function (array $params) {
        $w = $params['width'];
        return "/transforms/test_{$w}.jpg";
    });

    $builder = createBuilder($asset, $settings)->min(300)->max(500)->interval(200);

    $srcset = $builder->srcset();

    expect($srcset)->toHaveCount(2)
        ->and($srcset[0]['url'])->toBe('/transforms/test_300.jpg')
        ->and($srcset[0]['width'])->toBe(300)
        ->and($srcset[1]['url'])->toBe('/transforms/test_500.jpg')
        ->and($srcset[1]['width'])->toBe(500);
});

// --- Asset path in URL ---

it('includes asset path in imageboss url', function () {
    $asset = createMockAsset(path: 'photos/hero.jpg');
    $builder = createBuilder($asset)->width(800);

    $url = $builder->url();

    expect($url)->toContain('photos/hero.jpg');
});

it('sanitizes path with backslashes', function () {
    $asset = createMockAsset(path: 'photos\\sub\\test.jpg');
    $builder = createBuilder($asset)->width(800);

    $url = $builder->url();

    expect($url)->toContain('photos/sub/test.jpg')
        ->and($url)->not->toContain('\\');
});

it('sanitizes path with double dots', function () {
    $asset = createMockAsset(path: '../secret/test.jpg');
    $builder = createBuilder($asset)->width(800);

    $url = $builder->url();

    expect($url)->not->toContain('..')
        ->and($url)->toContain('secret/test.jpg');
});

// --- TransformResult ---

it('returns TransformResult from transform()', function () {
    $result = createBuilder()->min(300)->max(500)->interval(200)->transform();

    expect($result)->toBeInstanceOf(TransformResult::class);
});

it('provides first and last items from TransformResult', function () {
    $result = createBuilder()->min(300)->max(700)->interval(200)->transform();

    $first = $result->first();
    $last = $result->last();

    expect($first)->toBeInstanceOf(TransformResultItem::class)
        ->and($first->width)->toBe(300)
        ->and($last)->toBeInstanceOf(TransformResultItem::class)
        ->and($last->width)->toBe(700);
});

it('provides all items from TransformResult', function () {
    $result = createBuilder()->min(300)->max(700)->interval(200)->transform();

    $all = $result->all();

    expect($all)->toHaveCount(3)
        ->and($all[0])->toBeInstanceOf(TransformResultItem::class)
        ->and($all[0]->width)->toBe(300)
        ->and($all[1]->width)->toBe(500)
        ->and($all[2]->width)->toBe(700);
});

it('casts TransformResultItem to url string', function () {
    $result = createBuilder()->width(800)->transform();

    $last = $result->last();

    expect((string) $last)->toContain('width/800');
});

it('casts TransformResult to srcset string', function () {
    $result = createBuilder()->min(300)->max(500)->interval(200)->transform();

    expect((string) $result)->toContain('300w')
        ->and((string) $result)->toContain('500w');
});

it('returns srcset string from TransformResult', function () {
    $result = createBuilder()->min(300)->max(500)->interval(200)->transform();

    expect($result->srcset())->toContain('300w')
        ->and($result->srcset())->toContain('500w');
});

it('includes height in TransformResultItem when ratio is set', function () {
    $result = createBuilder()->min(300)->max(500)->interval(200)->ratio(16 / 9)->transform();

    $first = $result->first();

    expect($first->height)->toBe(169);
});

it('has null height in TransformResultItem when no ratio', function () {
    $result = createBuilder()->min(300)->max(500)->interval(200)->transform();

    $first = $result->first();

    expect($first->height)->toBeNull();
});

it('returns null first and last from empty TransformResult', function () {
    $builder = new NullImageBossBuilder();
    $result = $builder->transform();

    expect($result->first())->toBeNull()
        ->and($result->last())->toBeNull()
        ->and($result->all())->toBe([])
        ->and($result->srcset())->toBe('')
        ->and((string) $result)->toBe('');
});

// --- Placeholder with color ---

it('generates placeholder with color', function () {
    $placeholder = createBuilder()->width(800)->height(600)->placeholder('transparent');

    expect($placeholder)->toContain("style='background:transparent'")
        ->and($placeholder)->toContain("width='800'")
        ->and($placeholder)->toContain("height='600'");
});

it('generates placeholder without color by default', function () {
    $placeholder = createBuilder()->width(800)->height(600)->placeholder();

    expect($placeholder)->not->toContain('style')
        ->and($placeholder)->not->toContain('background');
});

it('generates placeholder with hex color', function () {
    $placeholder = createBuilder()->width(400)->height(300)->placeholder('#f0f0f0');

    expect($placeholder)->toContain("style='background:#f0f0f0'");
});
