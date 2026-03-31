<?php

namespace Noo\CraftImageboss\builders;

use craft\elements\Asset;
use craft\helpers\App;
use Noo\CraftImageboss\contracts\ImagePreset;
use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\Plugin;

class ImageBossBuilder
{
    private Asset $asset;

    private ?int $width = null;

    private ?int $height = null;

    private ?float $ratio = null;

    private ?int $min = null;

    private ?int $max = null;

    private ?int $interval = null;

    private ?string $format = null;

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
        if ($ratio === null || $ratio <= 0) {
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

        foreach (['min', 'max', 'ratio', 'interval', 'format'] as $key) {
            if (isset($config[$key])) {
                $this->$key = $config[$key];
            }
        }

        return $this;
    }

    public function url(): string
    {
        $width = $this->width ?? $this->getSettings()->defaultWidth;

        return $this->generateUrlForWidth($width);
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

        $fill = $color ? " style='background:{$color}'" : '';

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}'{$fill}%3E%3C/svg%3E";
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
        $focalPoint = $this->getFocalPoint();

        if ($focalPoint) {
            $opts[] = "fp-x:{$focalPoint['x']},fp-y:{$focalPoint['y']}";
        }

        $opts[] = 'format:' . ($this->format ?? 'auto');
        $segments[] = implode(',', $opts);

        $this->appendAssetPath($segments);

        return implode('/', $segments);
    }

    private function appendAssetPath(array &$segments): void
    {
        $volume = $this->asset->getVolume();
        $fs = $volume->getFs();

        $settings = $this->getSettings();

        if ($settings->includeVolumeFolder && property_exists($fs, 'path') && $fs->path !== '') {
            $fullPath = trim(App::parseEnv($fs->path), '/');
            $segments[] = basename($fullPath);
        }

        $subpath = $volume->getSubpath();
        if ($subpath !== '') {
            $segments[] = trim(App::parseEnv($subpath), '/');
        }

        $segments[] = $this->sanitizePath($this->asset->getPath());
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
        $min = $this->min ?? $settings->defaultWidth;
        $max = $this->max ?? $min;
        $interval = $this->interval ?? $settings->defaultInterval;

        $widths = [];

        for ($w = $min; $w <= $max; $w += $interval) {
            $widths[] = $w;
        }

        if (empty($widths) || end($widths) !== $max) {
            $widths[] = $max;
        }

        return $widths;
    }

    private function calculateHeight(int $width): ?int
    {
        if ($this->height) {
            return $this->height;
        }

        if (! $this->ratio) {
            return null;
        }

        return (int) round($width / $this->ratio);
    }

    private function sanitizePath(string $path): string
    {
        $path = str_replace(['\\', '..'], ['/', ''], $path);
        $path = (string) preg_replace('#/+#', '/', $path);

        return ltrim($path, '/');
    }

    protected function getSettings(): Settings
    {
        return Plugin::$plugin->getSettings();
    }
}
