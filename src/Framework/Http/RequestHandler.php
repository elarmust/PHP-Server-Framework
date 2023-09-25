<?php

/**
 * RequestHandler class is responsible for executing a stack of PSR-15 middlewares
 * and returning the resulting PSR-7 response.
 * 
 * Copyright © WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Framework\Http\Route;

use Framework\Core\ClassContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\Core\Psr\Response;

class RequestHandler implements RequestHandlerInterface {
    private ClassContainer $classContainer;
    private ResponseInterface $response;
    private Route $route;
    private array $middlewareStack = [];

    public function __construct(ClassContainer $classContainer, Route $route) {
        $this->classContainer = $classContainer;
        $this->route = $route;
        $this->middlewareStack = $this->route->getMiddlewareStack();
        $this->response = new Response('', 404);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        if (empty($this->middlewareStack)) {
            // Return response, if there are no more middleware left.
            return $this->response;
        }

        // Process the middleware
        $middleWare = array_shift($this->middlewareStack);
        $middleWare = $this->classContainer->get($middleWare, [$this->route], cache: false);
        $this->response = $middleWare->process($request, $this);
        return $this->response;
    }
}
