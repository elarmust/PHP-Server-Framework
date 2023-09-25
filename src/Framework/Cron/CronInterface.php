<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Cron;

interface CronInterface {
    /**
     * Run cron job.
     *
     * @return void
     */
    public function run(): void;

    /**
     * Get cron job name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get cron job schedule
     *
     * @return string
     */
    public function getSchedule(): string;

    /**
     * Set cron schedule.
     *
     * @return void
     */
    public function setSchedule(string $schedule): void;

    /**
     * Check cron job enabled status
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Enable disabled cron job.
     *
     * @return void
     */
    public function enable(): void;

    /**
     * Disable cron job without unregistering it.
     *
     * @return void
     */
    public function disable(): void;
}