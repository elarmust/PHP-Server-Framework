<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron;

use DateTime;
use Framework\Utils\TimeUtils;
use Framework\Task\TaskScheduler;
use Framework\Cron\Task\CronTaskDelay;
use Framework\Event\EventListenerInterface;

class HttpStart implements EventListenerInterface {
    private CronTaskDelay $cronTaskDelay;
    private TaskScheduler $taskScheduler;

    public function __construct(TaskScheduler $taskScheduler, CronTaskDelay $cronTaskDelay) {
        $this->cronTaskDelay = $cronTaskDelay;
        $this->taskScheduler = $taskScheduler;
    }

    public function __invoke(object $event): void {
        $nextMinute = new DateTime();
        $nextMinute->modify('+1 minute');
        $nextMinute->setTime($nextMinute->format('H'), $nextMinute->format('i'), 0);
        $this->taskScheduler->schedule($this->cronTaskDelay, TimeUtils::getMillisecondsToDateTime($nextMinute));
    }
}
