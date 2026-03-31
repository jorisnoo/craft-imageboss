<?php

namespace Noo\CraftImageboss\twig;

use craft\elements\Asset;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ImageBossTwigExtension extends AbstractExtension
{
    private ImageBossVariable $variable;

    public function __construct()
    {
        $this->variable = new ImageBossVariable();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('imageboss_srcset', [$this->variable, 'srcset']),
            new TwigFilter('imageboss_url', [$this->variable, 'url']),
            new TwigFilter('imageboss_placeholder', [$this->variable, 'placeholder']),
        ];
    }
}
