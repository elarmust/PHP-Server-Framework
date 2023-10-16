<?php

/**
 * This cron job is responsible for periodically removing expired user sessions based on the session expiration settings.
 *
 * @copyright WW Byte OÜ.
 */

namespace Framework\Http\Session\Cron;

use Framework\Cron\CronInterface;
use Framework\Http\Session\SessionManager;

class SessionCleanup implements CronInterface {
    private bool $enabled = true;
    private string $schedule = '* * * * *';

    /**
     * @param SessionManager $sessionManager
     */
    public function __construct(private SessionManager $sessionManager) {}

    /**
     * Run cron job.
     *
     * @return void
     */
    public function run(): void {
        foreach ($this->sessionManager->getSessions() as $sessionId => $session) {
            if ((time() - $session->getTimestamp()) > $this->sessionManager->getExpirationSeconds()) {
                $this->sessionManager->deleteSession($sessionId);
            }
        }
    }

    /**
     * Get cron job name
     *
     * @return string
     */
    public function getName(): string {
        return 'session_cleanup';
    }

    /**
     * Get cron job schedule
     *
     * @return string
     */
    public function getSchedule(): string {
        return $this->schedule;
    }

    /**
     * Set cron schedule.
     *
     * @return void
     */
    public function setSchedule(string $schedule): void {
        $this->schedule = $schedule;
    }

    /**
     * Check cron job enabled status
     *
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * Enable disabled cron job.
     *
     * @return void
     */
    public function enable(): void {
        $this->enabled = true;
    }

    /**
     * Disable cron job without unregistering it.
     *
     * @return void
     */
    public function disable(): void {
        $this->enabled = false;
    }
}
