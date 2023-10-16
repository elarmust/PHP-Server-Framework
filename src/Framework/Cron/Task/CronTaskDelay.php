<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Cron\Task;

use Framework\Cron\Task\CronTask;
use Framework\Task\TaskInterface;
use Framework\Task\TaskScheduler;

class CronTaskDelay implements TaskInterface {
    public function __construct(private TaskScheduler $taskScheduler, private CronTask $cronTask) {}

    public function getName(): string {
        return 'CronTaskDelay';
    }

    public function execute() {
        $this->taskScheduler->scheduleRecurring($this->cronTask, 60000);
    }
}
