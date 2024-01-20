<?php

/**
 * A middleware for processing a route controller stack.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use Framework\Http\Route;
use Framework\Container\ClassContainer;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerMiddleware implements ControllerStackInterface {
    private array $controllerStack = [];

    /**
     * @param ClassContainer $classContainer
     * @param Route $route
     */
    public function __construct(private ClassContainer $classContainer, private Route $route) {
        $this->controllerStack = $this->route->getControllerStack();
    }

    /**
     * Process the route controllers.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        return $this->execute($request, $handler->handle($request));
    }

    /**
     * Execute the next controller in the controller stack chain.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        if (empty($this->controllerStack)) {
            // Return response, if there are no more controllers left.
            return $response;
        }

        // Process the controller.
        $controllerClass = array_shift($this->controllerStack);
        $controllerClass = $this->classContainer->get($controllerClass, useCache: false);
        return $controllerClass->execute($request, $response, $this);
    }
}
