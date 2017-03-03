<?php

namespace yii2mod\scheduling;

use League\CLImate\CLImate;
use Yii;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\di\Instance;

/**
 * Class ScheduleController
 *
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
    public $scheduleFile = '@app/config/schedule.php';

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        return array_merge(parent::options($actionID),
            $actionID == 'run' ? ['scheduleFile'] : []
        );
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Yii::$app->has($this->schedule)) {
            $this->schedule = Instance::ensure($this->schedule, Schedule::className());
        } else {
            $this->schedule = Yii::createObject(Schedule::className());
        }
    }

    /**
     * Run scheduled commands
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
     * Render list of registered tasks
     */
    public function actionList()
    {
        $this->importScheduleFile();

        $climate = new CLImate();
        $data = [];
        $row = 0;

        foreach ($this->schedule->getEvents() as $event) {
            $data[] = [
                '#' => ++$row,
                'Task' => $event->getDescription(),
                'Expression' => $event->getExpression(),
                'Command to Run' => $event->command,
            ];
        }

        $climate->table($data);
    }

    /**
     * Import schedule file
     *
     * @throws InvalidConfigException
     */
    protected function importScheduleFile()
    {
        $scheduleFile = Yii::getAlias($this->scheduleFile);

        if (!file_exists($scheduleFile)) {
            throw new InvalidConfigException("Can not load schedule file {$this->scheduleFile}");
        }

        $schedule = $this->schedule;
        call_user_func(function () use ($schedule, $scheduleFile) {
            include $scheduleFile;
        });
    }
}
