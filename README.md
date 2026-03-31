# Craft ImageBoss

[ImageBoss](https://imageboss.me/) integration for [Craft CMS](https://craftcms.com/) with native transform fallback.

## Requirements

- PHP 8.3+
- Craft CMS 5

## Installation

```bash
composer require jorisnoo/craft-imageboss
```

Copy the config file to `config/imageboss.php`:

```bash
cp vendor/jorisnoo/craft-imageboss/src/config/imageboss.php config/imageboss.php
```

## Configuration

Set your ImageBoss credentials in `.env`:

```env
IMAGEBOSS_SOURCE=your-source
IMAGEBOSS_TOKEN=your-token  # optional, for URL signing
```

When `IMAGEBOSS_SOURCE` is not set, the plugin falls back to Craft's native image transforms.

### Config Options

| Option | Default | Description |
|--------|---------|-------------|
| `source` | `null` | ImageBoss source identifier |
| `token` | `null` | HMAC token for URL signing |
| `baseUrl` | `https://img.imageboss.me` | ImageBoss CDN base URL |
| `includeVolumeFolder` | `true` | Include volume folder name in URL path |
| `defaultWidth` | `1000` | Default width for `url()` |
| `defaultInterval` | `200` | Step size for srcset generation |
| `presets` | `[]` | Named preset configurations |

### Presets

The plugin supports two approaches for defining presets: config-based and interface-based.

#### Option 1: Config-Based Presets

Define presets in `config/imageboss.php`:

```php
'presets' => [
    'thumbnail' => [
        'min' => 200,      // minimum srcset width
        'max' => 700,      // maximum srcset width
        'ratio' => 1,      // aspect ratio (optional)
        'interval' => 250, // width step (optional)
    ],
    'hero' => [
        'min' => 640,
        'max' => 3840,
    ],
    'shareImage' => [
        'min' => 1200,
        'max' => 1200,
        'ratio' => 1200 / 630,
        'format' => 'jpg', // output format (optional)
    ],
],
```

#### Option 2: Interface-Based Presets

Implement the `ImagePreset` interface on your enum for self-contained presets:

```php
use Noo\CraftImageboss\concerns\HasImagePresetHelpers;
use Noo\CraftImageboss\contracts\ImagePreset;

enum Preset: string implements ImagePreset
{
    use HasImagePresetHelpers;

    case Default = 'default';
    case Thumbnail = 'thumbnail';
    case Card = 'card';
    case Hero = 'hero';

    /**
     * @return array{min: int, max: int, ratio?: float, interval?: int}
     */
    public function config(): array
    {
        return match ($this) {
            self::Default => ['min' => 320, 'max' => 2560],
            self::Thumbnail => ['min' => 200, 'max' => 700, 'ratio' => 1, 'interval' => 250],
            self::Card => ['min' => 300, 'max' => 800, 'ratio' => 4 / 5],
            self::Hero => ['min' => 640, 'max' => 3840],
        };
    }
}
```

The `HasImagePresetHelpers` trait provides convenience methods:

```php
Preset::Hero->min();      // 640
Preset::Hero->max();      // 3840
Preset::Card->ratio();    // 0.8
Preset::Thumbnail->interval(); // 250
```

## Usage

### Twig

The plugin registers an `imageboss` template variable and Twig filters.

#### Fluent API

```twig
{# Single URL #}
{{ imageboss.from(asset).width(800).url() }}
{{ imageboss.from(asset).width(800).ratio(16/9).url() }}

{# Responsive srcset with preset #}
{{ imageboss.from(asset).preset('hero').srcsetString() }}

{# Custom srcset range #}
{{ imageboss.from(asset).min(300).max(1200).interval(200).srcsetString() }}

{# Placeholder for lazy loading #}
{{ imageboss.from(asset).width(800).ratio(16/9).placeholder() }}
{{ imageboss.from(asset).preset('card').placeholder('#f0f0f0') }}
```

#### Shorthand Methods

```twig
{# With preset name #}
{{ imageboss.url(asset, 'hero') }}
{{ imageboss.srcset(asset, 'hero') }}
{{ imageboss.placeholder(asset, 'card') }}

{# With options array #}
{{ imageboss.url(asset, { width: 800, height: 600, format: 'webp' }) }}
{{ imageboss.srcset(asset, { min: 300, max: 1200, interval: 200 }) }}
{{ imageboss.placeholder(asset, { preset: 'card', color: '#e0e0e0' }) }}
```

#### Filters

```twig
{{ asset | imageboss_url({ width: 800 }) }}
{{ asset | imageboss_srcset('hero') }}
{{ asset | imageboss_placeholder({ color: '#e0e0e0' }) }}
```

#### Full Example

```twig
{% set result = imageboss.transform(asset, 'hero') %}

<img
    src="{{ result.last().url }}"
    srcset="{{ result.srcset() }}"
    sizes="100vw"
    width="{{ result.last().width }}"
    {% if result.last().height %}height="{{ result.last().height }}"{% endif %}
    alt="{{ asset.title }}"
>
```

With a placeholder for lazy loading:

```twig
<img
    src="{{ imageboss.placeholder(asset, 'hero') }}"
    data-srcset="{{ imageboss.srcset(asset, 'hero') }}"
    data-sizes="auto"
    alt="{{ asset.title }}"
>
```

### PHP

```php
use Noo\CraftImageboss\builders\ImageBossBuilder;

// Via the plugin variable
$builder = Plugin::$plugin->variable->from($asset);

$url = $builder->width(800)->url();
$srcset = $builder->preset('hero')->srcsetString();
$placeholder = $builder->width(800)->ratio(16/9)->placeholder();
```

### Builder Methods

| Method | Description |
|--------|-------------|
| `width(int)` | Set image width |
| `height(int)` | Set image height |
| `ratio(float)` | Set aspect ratio (width/height) |
| `min(int)` | Minimum width for srcset |
| `max(int)` | Maximum width for srcset |
| `interval(int)` | Width step for srcset |
| `format(string)` | Output format override |
| `preset(string\|BackedEnum\|ImagePreset)` | Apply preset configuration |
| `url()` | Generate single URL |
| `srcset()` | Generate srcset array |
| `srcsetString()` | Generate srcset string |
| `transform()` | Get `TransformResult` with `first()`, `last()`, `all()` |
| `placeholder(?string $color)` | Generate SVG data-URI placeholder |
| `aspectRatio()` | Get resolved aspect ratio |

All setter methods accept `null` safely and return `$this` for chaining.

### Example Output

`url()` returns a single URL:

```
https://img.imageboss.me/your-source/width/800/format:auto/assets/image.jpg
```

`srcsetString()` returns a comma-separated srcset string:

```
https://img.imageboss.me/.../width/640/... 640w, https://img.imageboss.me/.../width/1280/... 1280w
```

When an asset has a focal point set, it's automatically included in the URL:

```
https://img.imageboss.me/your-source/cover/800x450/fp-x:0.25,fp-y:0.75,format:auto/assets/image.jpg
```

## Features

- Responsive srcset generation with configurable width ranges and intervals
- Focal point support from Craft asset focal points
- URL signing with HMAC-SHA256
- SVG placeholder generation for lazy loading
- Named presets via config or typed enums
- Automatic fallback to Craft's native transforms

## License

MIT
