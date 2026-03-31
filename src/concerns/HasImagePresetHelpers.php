<?php

namespace Noo\CraftImageboss\concerns;

trait HasImagePresetHelpers
{
    public function min(): int
    {
        return $this->config()['min'];
    }

    public function max(): int
    {
        return $this->config()['max'];
    }

    public function ratio(): ?float
    {
        return $this->config()['ratio'] ?? null;
    }

    public function interval(): ?int
    {
        return $this->config()['interval'] ?? null;
    }
}
