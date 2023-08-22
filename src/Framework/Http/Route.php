<?php

/**
 * Class for constructing a Route which contains a route path, controllers, middlewares and request handler.
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Route {
    private $path;
    private $controllers = [];
    private $middlewares = [];
    private string $requestHandler;

    /**
     * Create a new route from path and RequestHandlerInterface.
     * 
     * @param string $path
     * @param string $requestHandler
     * @return Route
     */
    public function __construct(string $path, string $requestHandler) {
        $this->setRequestHandler($requestHandler);

        $this->path = $path;
    }

    /**
     * Set the RequestHandler for the Route.
     * 
     * @param string $requestHandler
     * @return Route
     */
    public function setRequestHandler(string $requestHandler): Route {
        if (!class_exists($requestHandler) || !in_array(RequestHandlerInterface::class, class_implements($requestHandler))) {
            throw new InvalidArgumentException($requestHandler . ' must implement ' . RequestHandlerInterface::class . '!');
        }

        $this->requestHandler = $requestHandler;
        return $this;
    }

    /**
     * Add controllers to the route.
     * 
     * @param array $controllers
     * @return Route
     */
    public function addControllers(array $controllers): Route {
        foreach ($controllers as $controller) {
            if (!is_object($controller)) {
                throw new InvalidArgumentException($controller . ' must be an Object, which implements ' . RouteControllerInterface::class .  '!');
            }

            if (!$controller instanceof RouteControllerInterface) {
                throw new InvalidArgumentException($controller::class . ' must implement ' . RouteControllerInterface::class . '!');
            }
        }

        $this->controllers = array_merge($this->controllers, $controllers);
        return $this;
    }

    /**
     * Add middlewares to the route.
     * 
     * @param array $middlewares
     * @return Route
     */
    public function addMiddlewares(array $middlewares): Route {
        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException($middleware . ' must implement ' . MiddlewareInterface::class . '!');
            }
        }

        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    /**
     * Replace middlewares.
     * 
     * @param array $middlewares
     * @return Route
     */
    public function setMiddlewareStack(array $middlewares): Route {
        $this->middlewares = $$middlewares;
        return $this;
    }

    /**
     * Replace controllers.
     * 
     * @param array $controllers
     * @return Route
     */
    public function setControllers(array $controllers): Route {
        $this->controllers = $controllers;
        return $this;
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
     * Get Controllers.
     * 
     * @return array
     */
    public function getControllers(): array {
        return $this->controllers;
    }

    /**
     * Get the Middleware stack.
     * 
     * @return array
     */
    public function getMiddlewareStack(): array {
        return $this->middlewares;
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