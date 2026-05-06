<?php

namespace Noo\CraftImageboss\builders;

use Noo\CraftImageboss\contracts\ImagePreset;

class NullImageBossBuilder extends ImageBossBuilder
{
    public function __construct() {}

    public function width(?int $width): static
    {
        return $this;
    }

    public function height(?int $height): static
    {
        return $this;
    }

    public function ratio(?float $ratio): static
    {
        return $this;
    }

    public function min(?int $min): static
    {
        return $this;
    }

    public function max(?int $max): static
    {
        return $this;
    }

    public function interval(?int $interval): static
    {
        return $this;
    }

    public function format(?string $format): static
    {
        return $this;
    }

    public function quality(?int $quality): static
    {
        return $this;
    }

    public function preset(ImagePreset|\BackedEnum|string $preset): static
    {
        return $this;
    }

    public function url(): string
    {
        return '';
    }

    /**
     * @return array<int, array{url: string, width: int, height: ?int}>
     */
    public function srcset(): array
    {
        return [];
    }

    public function srcsetString(): string
    {
        return '';
    }

    public function transform(): TransformResult
    {
        return new TransformResult([]);
    }

    public function placeholder(?string $color = null): string
    {
        return '';
    }

    public function aspectRatio(): ?float
    {
        return null;
    }
}
