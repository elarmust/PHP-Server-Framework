<?php

/**
 * Class for constructing a Route which contains a route path, controllers, middlewares and request handler.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use InvalidArgumentException;
use Framework\Http\Middleware;
use Framework\Http\RequestHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Framework\Http\Middlewares\ControllerMiddleware;

class Route {
    private string $path;
    private string $controllerStackClass;
    private array $controllerStack = [];
    private array $middlewares = [];
    private string $requestHandler;
    private static array $defaultMiddlewares = [];

    /**
     * Create a new route from path and RequestHandlerInterface.
     *
     * @param string $path
     * @param string $requestHandler
     */
    public function __construct(string $path, string $requestHandler = RequestHandler::class) {
        $this->setRequestHandler($requestHandler);
        $this->path = $path;
    }

    /**
     * Set the RequestHandler for the Route.
     *
     * @param string $requestHandler
     *
     * @throws InvalidArgumentException
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
     * @param array $controllers An array of ControllerInterface compatible controllers.
     *
     * @throws InvalidArgumentException
     * @return Route
     */
    public function setControllerStack(array $controllers): Route {
        $this->controllerStack = [];
        return $this->addControllers($controllers);
    }

    /**
     * Add a new controller to the list of controllers for this route.
     *
     * @param array $controllers An array of ControllerInterface compatible controllers.
     *
     * @throws InvalidArgumentException
     * @return Route
     */
    public function addControllers(array $controllers): Route {
        foreach ($controllers as $controller) {
            if (!class_exists($controller) || !in_array(ControllerInterface::class, class_implements($controller))) {
                throw new InvalidArgumentException($controller . ' must implement ' . ControllerInterface::class . '!');
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
     * Add new Middleware compatible middlewares to the middleware stack associated with this route.
     *
     * @param array $middlewares An array of Middleware compatible middlewares.
     *
     * @throws InvalidArgumentException
     * @return Route
     */
    public function addMiddlewares(array $middlewares): Route {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware) || !is_subclass_of($middleware, Middleware::class)) {
                throw new InvalidArgumentException($middleware . ' must extend ' . Middleware::class . '!');
            }

            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Add new Middleware compatible middlewares to the default middleware stack that will be applied to all routes.
     *
     * @param array $middlewares An array of Middleware compatible middlewares.
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public static function addDefaultMiddlewares(array $middlewares): void {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware) || !is_subclass_of($middleware, Middleware::class)) {
                throw new InvalidArgumentException($middleware . ' must extend ' . Middleware::class . '!');
            }

            self::$defaultMiddlewares[] = $middleware;
        }
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
            $existingKey = array_search($middleware, $this->middlewares);
            if ($existingKey) {
                unset($this->middlewares[$existingKey]);
            }
        }

        return $this;
    }

    /**
     * Remove a middleware from the default middleware stack that will be applied to all routes.
     *
     * @param array $middlewareClassNames
     *
     * @return void
     */
    public static function removeDefaultMiddlewares(array $middlewareClassNames): void {
        foreach ($middlewareClassNames as $id => $middleware) {
            $existingKey = array_search($middleware, self::$defaultMiddlewares);
            if ($existingKey) {
                unset(self::$defaultMiddlewares[$existingKey]);
            }
        }
    }

    /**
     * Replace existing middleware stack with new Middleware compatible middlewares.
     *
     * @param array $middlewares An array of Middleware compatible middlewares.
     *
     * @return Route
     */
    public function setMiddlewareStack(array $middlewares): Route {
        $this->middlewares = [];
        return $this->addMiddlewares($middlewares);
    }

    /**
     * Replace existing default middleware stack that will be applied to all routes with new Middleware compatible middlewares.
     *
     * @param array $middlewares An array of Middleware compatible middlewares.
     *
     * @return void
     */
    public static function setDefaultMiddlewareStack(array $middlewares): void {
        self::$defaultMiddlewares = [];
        self::addDefaultMiddlewares($middlewares);
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
     * Set the path for the route.
     *
     * @param string $path New path for the route.
     * @return Route Route instance.
     */
    public function setPath(string $path): Route {
        $this->path = $path;
        return $this;
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
     * Consists of default middlewares + route specific middlewares.
     *
     * @return array
     */
    public function getMiddlewareStack(): array {
        return array_merge(self::$defaultMiddlewares, $this->middlewares);
    }

    /**
     * Get Route RequestHandler.
     *
     * @return string
     */
    public function getRequestHandler(): string {
        return $this->requestHandler;
    }

    /**
     * Get the default middlewares for the route.
     *
     * @return array An array of default middlewares that will be applied to all routes.
     */
    public static function getDefaultMiddlewares(): array {
        return self::$defaultMiddlewares;
    }
}
