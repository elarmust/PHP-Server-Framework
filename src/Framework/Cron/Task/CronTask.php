<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Cron\Task;

use Framework\Cron\CronManager;
use Framework\Task\TaskInterface;

class CronTask implements TaskInterface {
    public function __construct(private CronManager $cron) {
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
