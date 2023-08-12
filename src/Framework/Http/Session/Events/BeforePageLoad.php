<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http\Session\Events;

use Framework\EventManager\Event;
use Framework\EventManager\EventListenerInterface;
use Framework\Http\Session\SessionManager;

class BeforePageLoad implements EventListenerInterface {
    private SessionManager $sessionManager;

    public function __construct(SessionManager $sessionManager) {
        $this->sessionManager = $sessionManager;
    }

    public function run(Event &$event): void {
        $cookieSessionId = $event->getData()['request']->cookie['PHPSESSID'] ?? null;
        $session = $this->sessionManager->getSession($cookieSessionId);

        // Update session id, if it has changed.\
        if ($cookieSessionId !== $session->getId()) {
            $event->getData()['response']->cookie('PHPSESSID', $session->getId());
        }
    }
}