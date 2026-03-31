<?php

namespace Noo\CraftImageboss\models;

use craft\base\Model;

class Settings extends Model
{
    public ?string $source = null;

    public ?string $token = null;

    public ?string $apiKey = null;

    public string $baseUrl = 'https://img.imageboss.me';

    public bool $includeVolumeFolder = true;

    public int $defaultWidth = 1000;

    public int $defaultInterval = 200;

    public array $presets = [];
}
