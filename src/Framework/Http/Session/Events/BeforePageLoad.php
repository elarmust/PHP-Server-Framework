<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http\Session\Events;

use Framework\FrameworkServer;
use Framework\EventManager\Event;
use Framework\Configuration\Configuration;
use Framework\Http\Session\SessionManager;
use Framework\EventManager\EventListenerInterface;

class BeforePageLoad implements EventListenerInterface {
    private SessionManager $sessionManager;
    private Configuration $configuration;
    private FrameworkServer $server;

    public function __construct(SessionManager $sessionManager, Configuration $configuration, FrameworkServer $server) {
        $this->sessionManager = $sessionManager;
        $this->configuration = $configuration;
        $this->server = $server;
    }

    public function run(Event &$event): void {
        $data = $event->getData();
        $cookieSessionId = $data['request']->cookie['PHPSESSID'] ?? null;
        $session = $this->sessionManager->getSession($cookieSessionId);

        // Send session cookie to user.
        if ($cookieSessionId !== $session->getId()) {
            $secure = false;
            if ($this->server->sslEnabled()) {
                $secure = true;
            }

            $data['response']->cookie(
                'PHPSESSID',
                $session->getId(),
                time() + ($this->configuration->getConfig('sessionExpirationSeconds') ?? 259200),
                '/',
                $this->configuration->getConfig('hostName') ?? '',
                $secure,
                true
            );

            $data['request']->cookie['PHPSESSID'] = $session->getId();
        }
    }
}