<?php

namespace Noo\CraftImageboss\builders;

class TransformResultItem implements \Stringable
{
    public readonly string $url;

    public readonly int $width;

    public readonly ?int $height;

    /**
     * @param  array{url: string, width: int, height: ?int}  $data
     */
    public function __construct(array $data)
    {
        $this->url = $data['url'];
        $this->width = $data['width'];
        $this->height = $data['height'] ?? null;
    }

    public function __toString(): string
    {
        return $this->url;
    }
}
