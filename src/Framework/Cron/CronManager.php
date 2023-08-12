<?php

/**
 * Cron management.
 * Allows you to register and run cron jobs.
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron;

use DateTime;
use Cron\CronExpression;
use Framework\Logger\Logger;
use Framework\Database\Database;
use Framework\Cron\CronInterface;
use Throwable;

class CronManager {
    private Database $database;
    private Logger $logger;
    private array $cronJobs = [];
    private array $cronJobsRunning = [];

    public function __construct(Database $database, Logger $logger) {
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Run a cron job
     *
     * @param CronInterface $cronJob Cron job object.
     * @param bool $force Ignore cron schedule.
     * @return void
     */
    public function runCronJob(CronInterface $cronJob, bool $force = false): void {
        if (!$cronJob->isEnabled()) {
            return;
        }

        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $cron = new CronExpression($cronJob->getSchedule());

        if ($force || ($cron->isDue($date) && !isset($this->cronJobsRunning[$cronJob->getName()]))) {
            $this->database->insert('cron_history', ['cron_job' => $cronJob->getName(), 'start_time' => $date]);
            $this->cronJobsRunning[$cronJob->getName()] = $time;
            $insertId = $this->database->query('SELECT MAX(id) FROM cron_history WHERE cron_job = ?', [$cronJob->getName()]);
            try {
                $cronJob->run();
            } catch (Throwable $e) {
                $this->logger->log(Logger::LOG_ERR, $e->getMessage(), 'framework');
                $this->logger->log(Logger::LOG_ERR, $e->getTraceAsString(), 'framework');
            }

            if ($insertId) {
                $this->database->update('cron_history', ['end_time' => date('Y-m-d H:i:s', time())], ['id' => $insertId[0]['MAX(id)']]);
            }

            unset($this->cronJobsRunning[$cronJob->getName()]);
        }
    }

    /**
     * Get a list of running cron jobs.
     *
     * @return array Returns an array with job name key and start time() value.
     */
    public function getRunningCronJobs(): array {
        return $this->cronJobsRunning;
    }

    /**
     * Returns next run date for a cron job.
     *
     * @param CronInterface $cronJob Cron job object.
     * @return DateTime
     */
    public function getNextRunDate(CronInterface $cronJob): DateTime {
        $cron = new CronExpression($cronJob->getSchedule());
        return $cron->getNextRunDate();
    }

    /**
     * Returns previous run date for a cron job.
     *
     * @param CronInterface $cronJob Cron job object.
     * @return DateTime
     */
    public function getPreviousRunDate(CronInterface $cronJob): DateTime {
        $cron = new CronExpression($cronJob->getSchedule());
        return $cron->getPreviousRunDate();
    }

    /**
     * Register a new cron job.
     *
     * @param CronInterface $cronJob Cron job object.
     * @return void
     */
    public function registerCronJob(CronInterface $cron): void {
        $this->cronJobs[$cron->getName()] = $cron;
    }

    /**
     * Unregister a cron job.
     *
     * @param CronInterface $cronJob Cron job object.
     * @return void
     */
    public function unregisterCronJob(string $jobName): void {
        if (!isset($this->cronJobs[$jobName])) {
            $this->logger->log(Logger::LOG_NOTICE, 'Unregistering nonexistent cron job: \'' . $jobName . '\'', 'framework');
            return;
        }

        unset($this->cronJobs[$jobName]);
    }

    /**
     * Check if cron job exists.
     *
     * @return bool
     */
    public function cronJobExists(string $jobName): bool {
        return isset($this->cronJobs[$jobName]);
    }

    /**
     * Get registered cron jobs.
     *
     * @return array
     */
    public function getCronJobs(): array {
        return $this->cronJobs;
    }
}
