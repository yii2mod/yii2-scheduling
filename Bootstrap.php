<?php

namespace yii2mod\scheduling;

use yii\base\BootstrapInterface;
use yii\console\Application;

/**
 * Class Bootstrap
 *
 * @package yii2mod\scheduling
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if ($app instanceof Application) {
            if (!isset($app->controllerMap['schedule'])) {
                $app->controllerMap['schedule'] = 'yii2mod\scheduling\ScheduleController';
            }
        }
    }
}
