<?php

namespace yii2mod\scheduling;

use Yii;
use yii\console\Controller;
use yii\di\Instance;

/**
 * Class ScheduleController
 * @package yii2mod\scheduling
 */
class ScheduleController extends Controller
{
    /**
     * @var Schedule
     */
    public $schedule = 'schedule';

    /**
     * @var string Schedule file that will be used to run schedule
     */
    public $scheduleFile;

    /**
     * Returns the names of valid options for the action (id)
     * @param string $actionID
     * @return array
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID),
            $actionID == 'run' ? ['scheduleFile'] : []
        );
    }

    /**
     * Initializes the object.
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        if (Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, Schedule::className());
        } else {
            $this->schedule = Yii::createObject(Schedule::className());
        }
        parent::init();
    }

    /**
     * Run scheduled commands
     * @return void
     */
    public function actionRun()
    {
        $this->importScheduleFile();

        $events = $this->schedule->dueEvents(Yii::$app);

        foreach ($events as $event) {
            $this->stdout('Running scheduled command: ' . $event->getSummaryForDisplay() . "\n");
            $event->run(Yii::$app);
        }

        if (count($events) === 0) {
            $this->stdout("No scheduled commands are ready to run.\n");
        }
    }

    /**
     * Import schedule file
     * @return void
     */
    protected function importScheduleFile()
    {
        if ($this->scheduleFile === null) {
            return;
        }

        $scheduleFile = Yii::getAlias($this->scheduleFile);
        if (file_exists($scheduleFile) == false) {
            $this->stderr('Can not load schedule file ' . $this->scheduleFile . "\n");
            return;
        }

        $schedule = $this->schedule;
        call_user_func(function () use ($schedule, $scheduleFile) {
            include $scheduleFile;
        });
    }
}
