<?php

namespace Framework\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface {
    /**
     * @param Route $route Route for which the middleware is applied to.
     */
    public function __construct(protected Route $route) {
    }

    /**
     * Get the route associated with the middleware.
     *
     * @return Route
     */
    public function getRoute(): Route {
        return $this->route;
    }

    /**
     * Process the incoming request and return the response.
     *
     * @param ServerRequestInterface $request Incoming request.
     * @param RequestHandlerInterface $handler Request handler.
     *
     * @return ResponseInterface Response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        return $handler->handle($request);
    }
}
