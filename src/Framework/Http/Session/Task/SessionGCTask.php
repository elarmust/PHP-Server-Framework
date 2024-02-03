<?php

/**
 * This task is responsible for clearing cache and cold storage of expired sessions.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session\Task;

use Framework\Vault\Vault;
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
        $this->logger->debug('Running session garbage collection task.');
        $table = Vault::getTable($this->session::getTableName());
        foreach ($table as $sessionId => $sessionData) {
            $session = $this->session->load($sessionId);
            if ((time() - $sessionData['timestamp']) > $this->session->getExpirationSeconds()) {
                $session->delete($sessionId);
            }
        }

        // Delete expired sessions from the database.
        $this->session->getDatabase()->query('
            DELETE FROM ' . $this->session::getTableName() . ' WHERE timestamp < ?
        ', [time() - $this->session->getExpirationSeconds()]
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
