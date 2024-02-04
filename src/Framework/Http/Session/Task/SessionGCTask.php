<?php

/**
 * This task is responsible for clearing cache and cold storage of expired sessions.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session\Task;

use Framework\Cache\Cache;
use Framework\Logger\Logger;
use Framework\Task\TaskInterface;
use Framework\Http\Session\Session;

class SessionGCTask implements TaskInterface {
    /**
     * @param Session $session
     */
    public function __construct(private Session $session, private Logger $logger) {
    }

    /**
     * Run task.
     *
     * @return void
     */
    public function execute(): void {
        $minTimestamp = time() - $this->session->getExpirationSeconds();
        $table = Cache::getTable($this->session::getTableName());
        foreach ($table as $sessionId => $sessionData) {
            if ($sessionData['timestamp'] < $minTimestamp) {
                $session = $this->session->getSession($sessionId);
                $session->delete($sessionId);
            }
        }

        // Delete expired sessions from the database.
        $this->session->getDatabase()->query('
            DELETE FROM ' . $this->session::getTableName() . ' WHERE timestamp < ?
        ', [$minTimestamp]
        );
    }

    /**
     * Get task name
     *
     * @return string
     */
    public function getName(): string {
        return 'session_gc';
    }
}
