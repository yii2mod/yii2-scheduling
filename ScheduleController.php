<?php

namespace yii2mod\scheduling;

use Carbon\Carbon;
use Cron\CronExpression;
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
            $this->schedule = Instance::ensure($this->schedule, Schedule::class);
        } else {
            $this->schedule = Yii::createObject(Schedule::class);
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
                'Task' => $event->getSummaryForDisplay(),
                'Expression' => $event->getExpression(),
                'Command to Run' => is_a($event, CallbackEvent::class)
                    ? $event->getSummaryForDisplay()
                    : $event->command,
                'Next run at' => $this->getNextRunDate($event),
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

    /**
     * Get the next scheduled run date for this event
     *
     * @param Event $event
     *
     * @return string
     */
    protected function getNextRunDate(Event $event)
    {
        $cron = CronExpression::factory($event->getExpression());
        $date = Carbon::now();

        if ($event->hasTimezone()) {
            $date->setTimezone($event->getTimezone());
        }

        return $cron->getNextRunDate()->format('Y-m-d H:i:s');
    }
}
