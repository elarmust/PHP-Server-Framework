<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron\Task;

use Framework\Cron\CronManager;
use Framework\Task\TaskInterface;

class CronTask implements TaskInterface {
    private CronManager $cron;

    public function __construct(CronManager $cron) {
        $this->cron = $cron;
    }

    public function getName(): string {
        return 'CronTask';
    }

    public function execute() {
        // Attempt to run all cron jobs.
        foreach ($this->cron->getCronJobs() as $cronJob) {
            $this->cron->runCronJob($cronJob);
        }
    }
}