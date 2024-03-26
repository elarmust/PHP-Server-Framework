<?php

/**
 * A middleware for processing a route controller stack.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http\Middlewares;

use ReflectionClass;
use Framework\Http\Controller;
use Framework\Http\Middleware;
use Framework\Container\ClassContainer;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerMiddleware extends Middleware implements ControllerStackInterface {
    private array $controllerStack = [];

    /**
     * @param ClassContainer $classContainer
     */
    public function __construct(private ClassContainer $classContainer) {
        // Get the controller stack from the route. A route can have multiple controllers.
        $this->controllerStack = $this->route->getControllerStack();
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
        return $this->next($request, $handler->handle($request));
    }

    /**
     * Execute the next controller in the controller stack chain.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     *
     * @return ResponseInterface
     */
    public function next(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        if (empty($this->controllerStack)) {
            // Return response, if there are no more controllers left.
            return $response;
        }

        // Process the controller.
        $controllerClass = array_shift($this->controllerStack);

        // Use reflection to instantiate the class without invoking the constructor.
        $reflection = new ReflectionClass($controllerClass);
        $instance = $reflection->newInstanceWithoutConstructor();

        // Invoke the parent constructor separately!
        $parentConstructor = $reflection->getParentClass()->getConstructor();
        $params = $this->classContainer->prepareFunctionArguments(Controller::class, parameters: [$this->getRoute(), $request, $response, $this]);
        $parentConstructor->invoke($instance, ...$params);

        // Now, invoke the child constructor separately!
        $childConstructor = $reflection->getConstructor();
        // If the child constructor is different from the parent constructor, invoke it.
        // Because if child constructor has not been defined, then it will use the parent constructor, which is already invoked.
        if ($childConstructor != $parentConstructor) {
            $params = $this->classContainer->prepareFunctionArguments($controllerClass);
            $childConstructor->invoke($instance, ...$params);
        }

        return $instance->process($request, $response, $this);
    }
}
