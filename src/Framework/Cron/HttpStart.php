<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron;

use DateTime;
use Framework\Utils\TimeUtils;
use Framework\EventManager\Event;
use Framework\Task\TaskScheduler;
use Framework\Cron\Task\CronTaskDelay;
use Framework\EventManager\EventListenerInterface;

class HttpStart implements EventListenerInterface {
    private CronTaskDelay $cronTaskDelay;
    private TaskScheduler $taskScheduler;

    public function __construct(TaskScheduler $taskScheduler, CronTaskDelay $cronTaskDelay) {
        $this->cronTaskDelay = $cronTaskDelay;
        $this->taskScheduler = $taskScheduler;
    }

    public function run(Event &$event): void {
        $nextMinute = new DateTime();
        $nextMinute->modify('+1 minute');
        $nextMinute->setTime($nextMinute->format('H'), $nextMinute->format('i'), 0);
        $this->taskScheduler->schedule($this->cronTaskDelay, TimeUtils::getMillisecondsToDateTime($nextMinute));
    }
}