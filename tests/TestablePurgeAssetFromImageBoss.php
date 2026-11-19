<?php

namespace Noo\CraftImageboss\Tests;

use Noo\CraftImageboss\listeners\PurgeAssetFromImageBoss;
use Noo\CraftImageboss\models\Settings;

/**
 * Test subclass that bypasses Plugin::$plugin->getSettings() dependency.
 */
class TestablePurgeAssetFromImageBoss extends PurgeAssetFromImageBoss
{
    public function __construct(
        private Settings $testSettings,
    ) {
        parent::__construct();
    }

    protected function getSettings(): Settings
    {
        return $this->testSettings;
    }
}
