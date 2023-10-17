<?php

/**
 * Middleware for initializing a session and sending a session cookie.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Http\Csrf;

use Framework\Framework;
use Framework\Utils\RouteUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Framework\Configuration\Configuration;
use Framework\Http\Session\SessionManager;
use OpenSwoole\Core\Psr\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddleware implements MiddlewareInterface {
    public function __construct(
        private SessionManager $sessionManager,
        private Configuration $configuration,
        private Framework $server,
        private Csrf $csrf
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $existingCookies = $request->getCookieParams();
        $cookieSessionId = $existingCookies['PHPSESSID'] ?? null;
        $session = $this->sessionManager->getSession($cookieSessionId);
        $token = $request->getQueryParams()['token'] ?? null;

        // Check the validity of the token.
        if (!in_array($request->getMethod(), ['GET', 'HEAD']) && !$this->csrf->validateCsrfToken($token ?? '', $session)) {
            return new Response('', 403);
        }

        return $handler->handle($request);
    }
}
