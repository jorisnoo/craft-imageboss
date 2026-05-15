<?php

namespace Noo\CraftImageboss\builders;

class TransformResult implements \Stringable
{
    /**
     * @param  array<int, array{url: string, width: int, height: ?int}>  $items
     */
    public function __construct(private readonly array $items) {}

    public function first(): ?TransformResultItem
    {
        if (empty($this->items)) {
            return null;
        }

        return new TransformResultItem($this->items[array_key_first($this->items)]);
    }

    public function last(): ?TransformResultItem
    {
        if (empty($this->items)) {
            return null;
        }

        return new TransformResultItem($this->items[array_key_last($this->items)]);
    }

    /**
     * @return array<int, TransformResultItem>
     */
    public function all(): array
    {
        return array_map(
            fn (array $item) => new TransformResultItem($item),
            $this->items,
        );
    }

    public function srcset(): string
    {
        $parts = [];

        foreach ($this->items as $item) {
            $parts[] = "{$item['url']} {$item['width']}w";
        }

        return implode(', ', $parts);
    }

    public function imageSet(?int $baseWidth = null): string
    {
        if (empty($this->items)) {
            return '';
        }

        $base = $baseWidth ?? $this->items[array_key_first($this->items)]['width'];

        if ($base < 1) {
            return '';
        }

        $parts = [];

        foreach ($this->items as $item) {
            $density = round($item['width'] / $base, 2);
            $parts[] = sprintf('url("%s") %sx', $item['url'], $density);
        }

        return 'image-set(' . implode(', ', $parts) . ')';
    }

    public function __toString(): string
    {
        return $this->srcset();
    }
}
