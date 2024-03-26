<?php

/**
 * RequestHandler class is responsible for executing a stack of PSR-15 middlewares
 * and returning the resulting PSR-7 response.
 *
 * Copyright © Elar Must.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Framework\Http\Response;
use Framework\Http\Middleware;
use ReflectionClass;
use Framework\Http\Route;
use Framework\Container\ClassContainer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface {
    private ResponseInterface $response;
    private array $middlewareStack = [];

    public function __construct(
        private ClassContainer $classContainer,
        private Route $route
    ) {
        // Get the middleware stack from the route.
        $middlewares = $this->route->getMiddlewareStack();
        // Controller stack class is the last middleware in the middleware stack.
        $this->middlewareStack = array_merge($middlewares, [$this->route->getControllerStackClass()]);
        $this->response = new Response('', 404);
    }

    /**
     * Handles the HTTP request by processing the middleware stack.
     *
     * @param ServerRequestInterface $request HTTP request object.
     *
     * @return ResponseInterface HTTP response object.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface {
        if (empty($this->middlewareStack)) {
            // Return response, if there are no more middlewares left.
            return $this->response;
        }

        // Process the middleware
        $middleWare = array_shift($this->middlewareStack);

        // Use reflection to instantiate the class without invoking the constructor.
        $reflection = new ReflectionClass($middleWare);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Invoke the parent constructor separately!
        $parentConstructor = $reflection->getParentClass()->getConstructor();
        $params = $this->classContainer->prepareFunctionArguments(Middleware::class, parameters: [$this->route]);
        $parentConstructor->invoke($instance, ...$params);

        // Now, invoke the child constructor separately!
        $childConstructor = $reflection->getConstructor();
        // If the child constructor is different from the parent constructor, invoke it.
        // Because if child constructor has not been defined, then it will use the parent constructor, which is already invoked.
        if ($childConstructor != $parentConstructor) {
            $params = $this->classContainer->prepareFunctionArguments($middleWare);
            $childConstructor->invoke($instance, ...$params);
        }

        $this->response = $instance->process($request, $this);
        return $this->response;
    }
}
