<?php

/**
 * Class for constructing a Route which contains a route path, controllers, middlewares and request handler.
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Route {
    private string $path;
    private string $controllerStackClass;
    private array $controllerStack = [];
    private array $middlewares = [];
    private string $requestHandler;

    /**
     * Create a new route from path and RequestHandlerInterface.
     * 
     * @param string $path
     * @param string $requestHandler
     */
    public function __construct(string $path, string $requestHandler) {
        $this->setRequestHandler($requestHandler);
        $this->path = $path;
    }

    /**
     * Set the RequestHandler for the Route.
     * 
     * @param string $requestHandler
     * 
     * @return Route
     * @throws InvalidArgumentException
     */
    public function setRequestHandler(string $requestHandler): Route {
        if (!class_exists($requestHandler) || !in_array(RequestHandlerInterface::class, class_implements($requestHandler))) {
            throw new InvalidArgumentException($requestHandler . ' must implement ' . RequestHandlerInterface::class . '!');
        }

        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * Set the ControllerStackInterface compatible class responsible for processing the controller stack.
     * 
     * @param string $controllers ControllerStackInterface compatible class name.
     * 
     * @return Route
     */
    public function setControllerStackClass(string $controllerStack): Route {
        if (!class_exists($controllerStack) || !in_array(ControllerStackInterface::class, class_implements($controllerStack))) {
            throw new InvalidArgumentException($controllerStack . ' must implement ' . ControllerStackInterface::class . '!');
        }

        $this->controllerStackClass = $controllerStack;
        return $this;
    }

    /**
     * Set the list of controllers for this route.
     * 
     * @param array $controllers An array of RouteControllerInterface compatible controllers.
     * 
     * @return Route
     * @throws InvalidArgumentException
     */
    public function setControllerStack(array $controllers): Route {
        $this->controllerStack = [];
        return $this->addControllers($controllers);
    }

    /**
     * Add a new controller to the list of controllers for this route.
     * 
     * @param array $controllers An array of RouteControllerInterface compatible controllers.
     * 
     * @return Route
     * @throws InvalidArgumentException
     */
    public function addControllers(array $controllers): Route {
        foreach ($controllers as $controller) {
            if (!class_exists($controller) || !in_array(RouteControllerInterface::class, class_implements($controller))) {
                throw new InvalidArgumentException($controller . ' must implement ' . RouteControllerInterface::class . '!');
            }

            $this->controllerStack[] = $controller;
        }

        return $this;
    }

    /**
     * Remove a new controller from the list of controllers for this route.
     * 
     * @param array $controllerClassNames An array of controller class names to remove.
     * 
     * @return Route
     */
    public function removeControllers(array $controllerClassNames): Route {
        foreach ($controllerClassNames as $id => $controller) {
            unset($this->controllerStack[$id]);
        }

        return $this;
    }

    /**
     * Add new MiddlewareInterface compatible middlewares to the middleware stack associated with this route.
     * 
     * @param array $middlewares An array of MiddlewareInterface compatible middlewares.
     * 
     * @return Route
     * @throws InvalidArgumentException
     */
    public function addMiddlewares(array $middlewares): Route {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware) || !in_array(MiddlewareInterface::class, class_implements($middleware))) {
                throw new InvalidArgumentException($middleware . ' must implement ' . MiddlewareInterface::class . '!');
            }

            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Remove a middleware from the middleware stack associated with this route.
     * 
     * @param array $middlewareClassNames
     * 
     * @return Route
     */
    public function removeMiddlewares(array $middlewareClassNames): Route {
        foreach ($middlewareClassNames as $id => $middleware) {
            unset($this->middlewares[$id]);
        }

        return $this;
    }

    /**
     * Replace existing middleware stack with new MiddlewareInterface compatible middlewares.
     * 
     * @param array $middlewares An array of MiddlewareInterface compatible middlewares.
     * 
     * @return Route
     */
    public function setMiddlewareStack(array $middlewares): Route {
        $this->middlewares = [];
        return $this->addMiddlewares($middlewares);
    }

    /**
     * Get the path this Route is registered for.
     * 
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Get the class responsible for processing the controller stack.
     * 
     * @return string Returns the default ControllerMiddleware, if none have been defined.
     */
    public function getControllerStackClass(): string {
        return $this->controllerStackClass ?? ControllerMiddleware::class;
    }

    /**
     * Get a list of controllers associated with this route.
     * 
     * @return array
     */
    public function getControllerStack(): array {
        return $this->controllerStack;
    }

    /**
     * Get the Middleware stack.
     * 
     * @return array
     */
    public function getMiddlewareStack(): array {
        // Controller stack class is the last middleware in the stack.
        return array_merge($this->middlewares, [$this->getControllerStackClass()]);
    }

    /**
     * Get Route RequestHandler.
     * 
     * @return string
     */
    public function getRequestHandler(): string {
        return $this->requestHandler;
    }
}