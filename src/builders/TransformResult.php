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

    public function __toString(): string
    {
        return $this->srcset();
    }
}
