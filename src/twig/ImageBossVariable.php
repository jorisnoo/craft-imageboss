<?php

namespace Noo\CraftImageboss\twig;

use craft\elements\Asset;
use Noo\CraftImageboss\builders\ImageBossBuilder;
use Noo\CraftImageboss\builders\NullImageBossBuilder;
use Noo\CraftImageboss\builders\TransformResult;

class ImageBossVariable
{
    public function from(?Asset $asset): ImageBossBuilder|NullImageBossBuilder
    {
        if ($asset === null) {
            return new NullImageBossBuilder();
        }

        return new ImageBossBuilder($asset);
    }

    public function url(?Asset $asset, string|array $options = []): string
    {
        return $this->applyOptions($this->from($asset), $options)->url();
    }

    public function srcset(?Asset $asset, string|array $options = []): string
    {
        return $this->applyOptions($this->from($asset), $options)->srcsetString();
    }

    public function transform(?Asset $asset, string|array $options = []): TransformResult
    {
        return $this->applyOptions($this->from($asset), $options)->transform();
    }

    public function placeholder(?Asset $asset, string|array $options = []): string
    {
        $color = null;

        if (is_array($options)) {
            $color = $options['color'] ?? null;
        }

        return $this->applyOptions($this->from($asset), $options)->placeholder($color);
    }

    private function applyOptions(
        ImageBossBuilder|NullImageBossBuilder $builder,
        string|array $options,
    ): ImageBossBuilder|NullImageBossBuilder {
        if (is_string($options)) {
            return $builder->preset($options);
        }

        if (isset($options['preset'])) {
            $builder->preset($options['preset']);
        }

        if (isset($options['width'])) {
            $builder->width($options['width']);
        }

        if (isset($options['height'])) {
            $builder->height($options['height']);
        }

        if (isset($options['ratio'])) {
            $builder->ratio($options['ratio']);
        }

        if (isset($options['min'])) {
            $builder->min($options['min']);
        }

        if (isset($options['max'])) {
            $builder->max($options['max']);
        }

        if (isset($options['interval'])) {
            $builder->interval($options['interval']);
        }

        if (isset($options['format'])) {
            $builder->format($options['format']);
        }

        return $builder;
    }
}
