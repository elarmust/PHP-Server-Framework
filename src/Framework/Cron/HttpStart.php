<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron;

use OpenSwoole\Timer;
use OpenSwoole\Coroutine;
use Framework\Cron\CronManager;
use Framework\EventManager\Event;
use Framework\EventManager\EventListenerInterface;

class HttpStart implements EventListenerInterface {
    private CronManager $cron;

    public function __construct(CronManager $cron) {
        $this->cron = $cron;
    }

    public function run(Event &$event): void {
        $proceed = true;
        Timer::tick(500, function () use (&$proceed) {
            if (date('s') == 0) {
                if ($proceed) {
                    // Attempt to run all cron jobs.
                    Coroutine::create(function () {
                        foreach ($this->cron->getCronJobs() as $cronJob) {
                            $this->cron->runCronJob($cronJob);
                        }
                    });

                    $proceed = false;
                }
            } else {
                $proceed = true;
            }
        });
    }
}