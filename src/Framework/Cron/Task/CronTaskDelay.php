<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron\Task;

use Framework\Cron\Task\CronTask;
use Framework\Task\TaskInterface;
use Framework\Task\TaskScheduler;

class CronTaskDelay implements TaskInterface {
    private CronTask $cronTask;
    private TaskScheduler $taskScheduler;

    public function __construct(TaskScheduler $taskScheduler, CronTask $cronTask) {
        $this->cronTask = $cronTask;
        $this->taskScheduler = $taskScheduler;
    }

    public function getName(): string {
        return 'CronTaskDelay';
    }

    public function execute() {
        $this->taskScheduler->scheduleRecurring($this->cronTask, 60000);
    }
}