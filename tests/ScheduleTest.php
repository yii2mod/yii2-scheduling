<?php

namespace yii2mod\scheduling\tests;

use Yii;
use yii2mod\scheduling\Schedule;

/**
 * Class ScheduleTest
 *
 * @package yii2mod\scheduling\tests
 */
class ScheduleTest extends TestCase
{
    /**
     * @var Schedule
     */
    private $_schedule;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schedule = Yii::createObject(Schedule::class);
        $this->importScheduleFile();
    }

    // Tests :
    public function testGetEvents()
    {
        $events = $this->_schedule->getEvents();
        $event = $events[0];

        $this->assertNotEmpty($events);
        $this->assertCount(2, $events);
        $this->assertEquals('Show list of files', $event->description);
        $this->assertEquals('ls', $event->command);
        $this->assertEquals('* * * * * *', $event->expression);

        $event = $events[1];

        $this->assertEquals('Execute migrations', $event->description);
        $this->assertContains('yii migrate', $event->command);
        $this->assertEquals('0 0 * * * *', $event->expression);
    }

    /**
     * Import schedule file
     */
    protected function importScheduleFile()
    {
        $scheduleFile = Yii::getAlias('@yii2mod/tests/scheduling/data/schedule.php');

        $schedule = $this->_schedule;
        call_user_func(function () use ($schedule, $scheduleFile) {
            include $scheduleFile;
        });
    }
}
