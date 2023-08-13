<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http\Session\Events;

use Framework\EventManager\Event;
use Framework\Configuration\Configuration;
use Framework\Http\Session\SessionManager;
use Framework\EventManager\EventListenerInterface;
use Swoole\Coroutine\Http\Server;

class BeforePageLoad implements EventListenerInterface {
    private SessionManager $sessionManager;
    private Configuration $configuration;
    private Server $server;

    public function __construct(SessionManager $sessionManager, Configuration $configuration, Server $server) {
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
            if ($this->server->ssl) {
                $secure = true;
            }

            $data['response']->cookie(
                name: 'PHPSESSID',
                value: $session->getId(),
                expires: time() + ($this->configuration->getConfig('sessionExpirationSeconds') ?? 259200),
                domain: $this->configuration->getConfig('hostName') ?? '',
                secure: $secure,
                httponly: true
            );

            $data['request']->cookie['PHPSESSID'] = $session->getId();
        }
    }
}