<?php

/**
 * Middleware for initializing a session and sending a session cookie.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http\Middlewares;

use Framework\Http\Response;
use Framework\Http\Middleware;
use Framework\Http\Mime\MimeTypes;
use Framework\Http\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Framework\Configuration\Configuration;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CsrfMiddleware extends Middleware {
    public function __construct(
        private Session $session,
        private Configuration $configuration,
        private MimeTypes $mimeTypes
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
        // CSRF depends on session, so if session is disabled, we can't check CSRF.
        if ($this->configuration->getConfig('session.enabled') === false) {
            return $handler->handle($request);
        }

        $existingCookies = $request->getCookieParams();
        $cookieSessionId = $existingCookies[$this->session->getCookieName()] ?? null;
        $session = $this->session->getSession($cookieSessionId);
        $token = $request->getHeaderLine('X-CSRF-Token') ?: $request->getHeaderLine('X-XSRF-Token') ?: $request->getQueryParams()['csrftoken'] ?? $request->getParsedBody()['csrftoken'] ?? null;

        // Check the validity of the token.
        if (!$session->validateCsrfToken($token ?? '', $session)) {
            return new Response($this->mimeTypes, '', 403);
        }

        return $handler->handle($request);
    }
}
