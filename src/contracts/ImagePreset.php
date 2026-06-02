<?php

namespace Noo\CraftImageboss\contracts;

interface ImagePreset
{
    /**
     * @return array{min: int, max: int, ratio?: float, interval?: int, format?: string, quality?: int, animation?: bool}
     */
    public function config(): array;
}
