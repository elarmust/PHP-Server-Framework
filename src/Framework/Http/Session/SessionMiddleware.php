<?php

/**
 * Middleware for initializing a session and sending a session cookie.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http\Session;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Framework\Configuration\Configuration;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Framework\Framework;

class SessionMiddleware implements MiddlewareInterface {
    public function __construct(
        private Session $session,
        private Configuration $configuration,
        private Framework $server
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $existingCookies = $request->getCookieParams();
        $cookieSessionId = $existingCookies['PHPSESSID'] ?? null;
        $session = $this->session->load($cookieSessionId);

        // Send session cookie to user.
        if ($cookieSessionId !== $session->id()) {
            $secure = $this->server->sslEnabled();

            $cookieParams = $request->getCookieParams();
            $cookieParams['PHPSESSID'] = $session->id();
            $request = $request->withCookieParams($cookieParams);

            $expiration = time() + ($this->configuration->getConfig('sessionExpirationSeconds') ?? 259200);
            $expiresFormatted = gmdate('D, d M Y H:i:s T', $expiration);

            // Create a new cookie string with the specified attributes.
            $cookieString = 'PHPSESSID=' . $session->id() . '; path=/;';
            if ($secure) {
                $cookieString .= ' secure;';
            }

            $cookieString .= ' HttpOnly; expires=' . $expiresFormatted . '; domain=' . ($this->configuration->getConfig('hostName') ?? '') . ';';

            $response = $handler->handle($request);
            $response = $response->withAddedHeader('Set-Cookie', $cookieString);
            return $response;
        }

        return $handler->handle($request);
    }
}
