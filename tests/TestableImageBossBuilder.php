<?php

namespace Noo\CraftImageboss\Tests;

use craft\elements\Asset;
use Noo\CraftImageboss\builders\ImageBossBuilder;
use Noo\CraftImageboss\models\Settings;

/**
 * Test subclass that bypasses Plugin::$plugin->getSettings() dependency.
 */
class TestableImageBossBuilder extends ImageBossBuilder
{
    private Settings $testSettings;

    public function __construct(Asset $asset, Settings $settings)
    {
        parent::__construct($asset);
        $this->testSettings = $settings;
    }

    protected function getSettings(): Settings
    {
        return $this->testSettings;
    }
}
