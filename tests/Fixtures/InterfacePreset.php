<?php

namespace Noo\CraftImageboss\Tests\Fixtures;

use Noo\CraftImageboss\concerns\HasImagePresetHelpers;
use Noo\CraftImageboss\contracts\ImagePreset;

enum InterfacePreset: string implements ImagePreset
{
    use HasImagePresetHelpers;

    case Custom = 'custom';
    case WithRatio = 'with_ratio';
    case WithInterval = 'with_interval';

    /**
     * @return array{min: int, max: int, ratio?: float, interval?: int, format?: string, quality?: int}
     */
    public function config(): array
    {
        return match ($this) {
            self::Custom => ['min' => 100, 'max' => 500],
            self::WithRatio => ['min' => 200, 'max' => 600, 'ratio' => 2.0],
            self::WithInterval => ['min' => 150, 'max' => 450, 'interval' => 150],
        };
    }
}
