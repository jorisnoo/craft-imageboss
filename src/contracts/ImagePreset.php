<?php

namespace Noo\CraftImageboss\contracts;

interface ImagePreset
{
    /**
     * @return array{min: int, max: int, ratio?: float, interval?: int, format?: string, quality?: int}
     */
    public function config(): array;
}
