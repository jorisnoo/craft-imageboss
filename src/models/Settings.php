<?php

namespace Noo\CraftImageboss\models;

use craft\base\Model;

class Settings extends Model
{
    public ?string $source = null;

    public ?string $secret = null;

    public string $baseUrl = 'https://img.imageboss.me';

    public bool $useCloudSourcePath = true;

    public int $defaultWidth = 1000;

    public int $defaultInterval = 200;

    public array $presets = [];
}
