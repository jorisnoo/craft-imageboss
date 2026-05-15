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

        foreach (['preset', 'width', 'height', 'ratio', 'min', 'max', 'interval', 'format'] as $key) {
            if (isset($options[$key])) {
                $builder->$key($options[$key]);
            }
        }

        return $builder;
    }
}
