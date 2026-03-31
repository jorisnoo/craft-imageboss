<?php

namespace Noo\CraftImageboss\twig;

use craft\elements\Asset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImageBossTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('imageboss_srcset', [$this, 'srcsetFilter']),
            new TwigFilter('imageboss_url', [$this, 'urlFilter']),
            new TwigFilter('imageboss_placeholder', [$this, 'placeholderFilter']),
        ];
    }

    public function srcsetFilter(?Asset $asset, string|array $options = []): string
    {
        $variable = new ImageBossVariable();

        return $variable->srcset($asset, $options);
    }

    public function urlFilter(?Asset $asset, string|array $options = []): string
    {
        $variable = new ImageBossVariable();

        return $variable->url($asset, $options);
    }

    public function placeholderFilter(?Asset $asset, string|array $options = []): string
    {
        $variable = new ImageBossVariable();

        return $variable->placeholder($asset, $options);
    }
}
