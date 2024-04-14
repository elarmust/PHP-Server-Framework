<?php

/**
 * Middleware for initializing a session and sending a session cookie.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http\Middlewares;

use Framework\Http\Middleware;
use Framework\Http\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware extends Middleware {
    public function __construct(
        private Session $session
    ) {
    }

    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $cookieName = $this->session->getCookieName();
        $existingCookies = $request->getCookieParams();
        $cookieSessionId = $existingCookies[$cookieName] ?? null;
        $session = $this->session->getSession($cookieSessionId);
        $request = $request->withAttribute('session', $session);

        if ($cookieSessionId === $session->id()) {
            return $handler->handle($request);
        }

        $cookieParams = $request->getCookieParams();
        $cookieParams[$cookieName] = $session->id();
        $request = $request->withCookieParams($cookieParams);

        $expiration = time() + $session->getExpirationSeconds();
        $expiresFormatted = gmdate('D, d M Y H:i:s T', $expiration);

        // Create a new cookie string with the specified attributes.
        $cookieString = $cookieName . '=' . $session->id() . '; path=' . $session->getSessionPath() . ';';
        if ($session->getSecure() === true) {
            $cookieString .= ' secure;';
        }

        if ($session->getHttpOnly() === true) {
            $cookieString .= ' HttpOnly;';
        }

        $domain = $session->getSessionDomain();
        if ($domain) {
            $cookieString .= ' domain=' . $domain . ';';
        }

        $cookieString .= ' expires=' . $expiresFormatted . ';';

        $response = $handler->handle($request);
        $response = $response->withAddedHeader('Set-Cookie', $cookieString);
        return $response;
    }
}
