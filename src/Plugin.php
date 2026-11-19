<?php

namespace Noo\CraftImageboss;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\elements\Asset;
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

        Craft::$app->getView()->registerTwigExtension(new ImageBossTwigExtension());

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            static function (Event $event) {
                $event->sender->set('imageboss', ImageBossVariable::class);
            }
        );

        $settings = $this->getSettings();
        if ($settings->source && $settings->apiKey) {
            $listener = new PurgeAssetFromImageBoss();
            Event::on(Asset::class, Asset::EVENT_BEFORE_SAVE, [$listener, 'beforeSave']);
            Event::on(Asset::class, Asset::EVENT_AFTER_SAVE, [$listener, 'afterSave']);
            Event::on(Asset::class, Asset::EVENT_AFTER_DELETE, [$listener, 'afterDelete']);
        }
    }

    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }
}
