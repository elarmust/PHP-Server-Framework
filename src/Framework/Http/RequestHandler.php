<?php

/**
 * RequestHandler class is responsible for executing a stack of PSR-15 middlewares
 * and returning the resulting PSR-7 response.
 *
 * Copyright © WW Byte OÜ.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Framework\Http\Route;
use Framework\Container\ClassContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\Core\Psr\Response;

class RequestHandler implements RequestHandlerInterface {
    private ResponseInterface $response;
    private array $middlewareStack = [];

    public function __construct(
        private ClassContainer $classContainer,
        private Route $route
    ) {
        $this->middlewareStack = $this->route->getMiddlewareStack();
        $this->response = new Response('', 404);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        if (empty($this->middlewareStack)) {
            // Return response, if there are no more middlewares left.
            return $this->response;
        }

        // Process the middleware
        $middleWare = array_shift($this->middlewareStack);
        $middleWare = $this->classContainer->get($middleWare, [$this->route], singleton: false);
        $this->response = $middleWare->process($request, $this);
        return $this->response;
    }
}
