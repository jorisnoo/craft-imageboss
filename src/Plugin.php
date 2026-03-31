<?php

namespace Noo\CraftImageboss;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\services\Assets;
use craft\web\twig\variables\CraftVariable;
use Noo\CraftImageboss\listeners\PurgeAssetFromImageBoss;
use Noo\CraftImageboss\models\Settings;
use Noo\CraftImageboss\twig\ImageBossTwigExtension;
use Noo\CraftImageboss\twig\ImageBossVariable;
use yii\base\Event;

class Plugin extends BasePlugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        if (Craft::$app->getRequest()->getIsSiteRequest()) {
            Craft::$app->getView()->registerTwigExtension(new ImageBossTwigExtension());
        }

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function (Event $event) {
                $event->sender->set('imageboss', ImageBossVariable::class);
            }
        );

        if ($this->getSettings()->apiKey) {
            $listener = new PurgeAssetFromImageBoss();
            Event::on(
                Assets::class,
                Assets::EVENT_AFTER_REPLACE_ASSET,
                [$listener, 'handle']
            );
        }
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
