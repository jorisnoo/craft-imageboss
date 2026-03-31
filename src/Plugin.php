<?php

namespace Noo\CraftImageboss;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\web\twig\variables\CraftVariable;
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
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
