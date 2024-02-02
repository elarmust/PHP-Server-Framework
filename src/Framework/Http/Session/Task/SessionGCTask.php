<?php

/**
 * This task is responsible for clearing cache and cold storage of expired sessions.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session\Task;

use Framework\Http\Session\SessionManager;
use Framework\Task\TaskInterface;

class SessionGCTask implements TaskInterface {

    /**
     * @param SessionManager $sessionManager
     */
    public function __construct(private SessionManager $sessionManager) {
    }

    /**
     * Run task.
     *
     * @return void
     */
    public function execute(): void {
        foreach ($this->sessionManager->getSessionIds() as $sessionId) {
            //$session = $this->sessionManager->loadSession($sessionId);
            //if ((time() - $session->getTimestamp()) > $this->sessionManager->getExpirationSeconds()) {
            //    $this->sessionManager->deleteSession($sessionId);
            //}
        }
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
