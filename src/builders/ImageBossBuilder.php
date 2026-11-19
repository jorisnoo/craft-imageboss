<?php

namespace Noo\CraftImageboss\builders;

use craft\elements\Asset;
use Noo\CraftImageboss\contracts\ImagePreset;
use Noo\CraftImageboss\helpers\AssetPath;
use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\Plugin;

class ImageBossBuilder
{
    private const MAX_CANDIDATES = 50;

    private Asset $asset;

    private ?int $width = null;

    private ?int $height = null;

    private ?float $ratio = null;

    private ?int $min = null;

    private ?int $max = null;

    private ?int $interval = null;

    private ?string $format = null;

    private ?int $quality = null;

    private bool $animation = false;

    private ?string $download = null;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function width(?int $width): static
    {
        if ($width === null || $width < 1) {
            return $this;
        }

        $this->width = $width;

        return $this;
    }

    public function height(?int $height): static
    {
        if ($height === null || $height < 1) {
            return $this;
        }

        $this->height = $height;

        return $this;
    }

    public function ratio(?float $ratio): static
    {
        if ($ratio === null || ! is_finite($ratio) || $ratio <= 0) {
            return $this;
        }

        $this->ratio = $ratio;

        return $this;
    }

    public function min(?int $min): static
    {
        if ($min === null || $min < 1) {
            return $this;
        }

        $this->min = $min;

        return $this;
    }

    public function max(?int $max): static
    {
        if ($max === null || $max < 1) {
            return $this;
        }

        $this->max = $max;

        return $this;
    }

    public function interval(?int $interval): static
    {
        if ($interval === null || $interval < 1) {
            return $this;
        }

        $this->interval = $interval;

        return $this;
    }

    public function format(?string $format): static
    {
        if ($format === null) {
            return $this;
        }

        $this->format = $format;

        return $this;
    }

    public function quality(?int $quality): static
    {
        if ($quality === null || $quality < 1) {
            return $this;
        }

        $this->quality = $quality;

        return $this;
    }

    public function animation(?bool $animation = true): static
    {
        if (! $animation) {
            return $this;
        }

        $this->animation = true;

        return $this;
    }

    public function download(bool|string|null $download): static
    {
        if ($download === null || $download === false) {
            return $this;
        }

        if (is_string($download)) {
            $name = trim(preg_replace('~[/\\\\\x00-\x1f\x7f]+~', '-', $download), " .-\t");
            if ($name === '') {
                return $this;
            }
            $this->download = rawurlencode($name);
        } else {
            $this->download = '1';
        }

        return $this;
    }

    public function aspectRatio(): ?float
    {
        if ($this->ratio) {
            return $this->ratio;
        }

        if ($this->width && $this->height) {
            return $this->width / $this->height;
        }

        return null;
    }

    public function preset(ImagePreset|\BackedEnum|string $preset): static
    {
        if ($preset instanceof ImagePreset) {
            $config = $preset->config();
        } else {
            $presetName = $preset instanceof \BackedEnum ? $preset->value : $preset;
            $settings = $this->getSettings();
            $availablePresets = array_keys($settings->presets);

            if (! in_array($presetName, $availablePresets, true)) {
                return $this;
            }

            $config = $settings->presets[$presetName] ?? [];
        }

        if (empty($config)) {
            return $this;
        }

        foreach (['min', 'max', 'ratio', 'interval', 'format', 'quality', 'animation'] as $key) {
            if (isset($config[$key])) {
                $this->$key($config[$key]);
            }
        }

        return $this;
    }

    public function url(): string
    {
        if ($this->download !== null && ! $this->hasTransformDimensions()) {
            return $this->cdn();
        }

        $width = $this->width ?? $this->max ?? $this->min ?? max(1, $this->getSettings()->defaultWidth);

        return $this->generateUrlForWidth($width);
    }

    private function hasTransformDimensions(): bool
    {
        return $this->width !== null
            || $this->height !== null
            || $this->ratio !== null
            || $this->min !== null
            || $this->max !== null;
    }

    /**
     * @param  array{compression?: bool}  $options
     */
    public function cdn(array $options = []): string
    {
        $settings = $this->getSettings();

        if (! $settings->source) {
            return $this->asset->getUrl() ?? '';
        }

        $segments = ['', $settings->source, 'cdn'];

        $opts = [];

        if (array_key_exists('compression', $options)) {
            $opts[] = 'compression:' . ($options['compression'] ? 'true' : 'false');
        }

        if ($this->download !== null) {
            $opts[] = "download:{$this->download}";
        }

        if (! empty($opts)) {
            $segments[] = implode(',', $opts);
        }

        $segments[] = AssetPath::for($this->asset, $settings);

        return $this->signPath(implode('/', $segments));
    }

    /**
     * @return array<int, array{url: string, width: int, height: ?int}>
     */
    public function srcset(): array
    {
        return array_map(fn (int $width) => [
            'url' => $this->generateUrlForWidth($width),
            'width' => $width,
            'height' => $this->calculateHeight($width),
        ], $this->generateWidths());
    }

    public function transform(): TransformResult
    {
        return new TransformResult($this->srcset());
    }

    public function srcsetString(): string
    {
        return $this->transform()->srcset();
    }

    public function placeholder(?string $color = null): string
    {
        [$width, $height] = $this->resolvePlaceholderDimensions();

        if ($width === null || $height === null) {
            return '';
        }

        // Accept CSS color literals, not arbitrary style declarations or resource URLs.
        $fill = $color !== null && preg_match('/\A(?:[a-z]+|#[0-9a-f]{3,4}|#[0-9a-f]{6}|#[0-9a-f]{8}|(?:rgb|hsl)a?\([0-9.,% +\/-]+\))\z/i', $color)
            ? " style='background:{$color}'" : '';

        // Fully encoded: unencoded spaces would split the URI into bogus srcset
        // candidates, and a `#` from a hex color would truncate it at the fragment.
        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode(
            "<svg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}'{$fill}/>"
        );
    }

    /**
     * @return array{?int, ?int}
     */
    private function resolvePlaceholderDimensions(): array
    {
        if ($this->width && $this->height) {
            return [$this->width, $this->height];
        }

        if ($this->width && $this->ratio) {
            return [$this->width, $this->calculateHeight($this->width)];
        }

        if ($this->min && $this->ratio) {
            return [$this->min, $this->calculateHeight($this->min)];
        }

        $assetWidth = $this->asset->getWidth();
        $assetHeight = $this->asset->getHeight();

        if ($assetWidth && $assetHeight) {
            return [$assetWidth, $assetHeight];
        }

        return [null, null];
    }

    private function generateUrlForWidth(int $width): string
    {
        $settings = $this->getSettings();
        $height = $this->calculateHeight($width);

        if (! $settings->source) {
            return $this->generateCraftTransformUrl($width, $height);
        }

        return $this->signPath($this->buildImageBossPath($width, $height));
    }

    private function buildImageBossPath(int $width, ?int $height): string
    {
        $settings = $this->getSettings();
        $segments = ['', $settings->source];

        if ($height) {
            $segments[] = "cover/{$width}x{$height}";
        } else {
            $segments[] = "width/{$width}";
        }

        $opts = [];
        $focalPoint = $this->getFocalPoint() ?? ['x' => 0.5, 'y' => 0.5];

        $opts[] = "fp-x:{$focalPoint['x']},fp-y:{$focalPoint['y']}";
        $opts[] = 'format:' . ($this->format ?? 'auto');

        if ($this->quality !== null) {
            $opts[] = "quality:{$this->quality}";
        }

        if ($this->animation) {
            $opts[] = 'animation:true';
        }

        if ($this->download !== null) {
            $opts[] = "download:{$this->download}";
        }

        $segments[] = implode(',', $opts);

        $segments[] = AssetPath::for($this->asset, $settings);

        return implode('/', $segments);
    }

    /**
     * @return array{x: float, y: float}|null
     */
    private function getFocalPoint(): ?array
    {
        if (! $this->asset->getHasFocalPoint()) {
            return null;
        }

        $fp = $this->asset->getFocalPoint();

        if (! $fp || ! isset($fp['x'], $fp['y'])) {
            return null;
        }

        return [
            'x' => round($fp['x'], 2),
            'y' => round($fp['y'], 2),
        ];
    }

    private function generateCraftTransformUrl(int $width, ?int $height): string
    {
        $params = ['width' => $width];

        if ($height) {
            $params['height'] = $height;
            $params['mode'] = 'crop';
        }

        if ($this->format) {
            $params['format'] = $this->format;
        }

        if ($this->quality !== null) {
            $params['quality'] = $this->quality;
        }

        return $this->asset->getUrl($params) ?? '';
    }

    private function signPath(string $path): string
    {
        $settings = $this->getSettings();

        if (! $settings->token) {
            return $settings->baseUrl . $path;
        }

        $bossToken = hash_hmac('sha256', $path, $settings->token);

        return $settings->baseUrl . $path . '?bossToken=' . $bossToken;
    }

    /**
     * @return array<int>
     */
    private function generateWidths(): array
    {
        $settings = $this->getSettings();
        $min = $this->min ?? $this->width ?? max(1, $settings->defaultWidth);
        $max = $this->max ?? $this->width ?? $min;
        $min = min($min, $max);
        // Spread very large ranges across the budget; always keep both endpoints.
        $minStep = intdiv(max(0, $max - $min - 1), self::MAX_CANDIDATES - 1) + 1;
        $interval = max($minStep, $this->interval ?? $settings->defaultInterval);

        $widths = [];

        for ($w = $min; $w < $max; $w += $interval) {
            $widths[] = $w;
        }

        $widths[] = $max;

        return $widths;
    }

    private function calculateHeight(int $width): ?int
    {
        $ratio = $this->aspectRatio();
        return $ratio === null ? $this->height : max(1, (int) round($width / $ratio));
    }

    protected function getSettings(): Settings
    {
        return Plugin::$plugin->getSettings();
    }
}
