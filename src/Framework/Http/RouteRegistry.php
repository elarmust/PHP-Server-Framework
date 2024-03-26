<?php

/**
 * Registry for RequestHandlers, Controllers, Middlewares and their associated routes.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use Framework\Http\Route;
use InvalidArgumentException;
use Framework\Http\Middleware;

class RouteRegistry {
    private array $routes = [];
    private array $defaultMiddlewares = [];

    /**
     * Register a new Route.
     *
     * @param string $path
     * @param string $requestHandler
     *
     * @return Route
     */
    public function registerRoute(Route $route): RouteRegistry {
        $this->routes[$route->getPath()] = $route;
        return $this;
    }

    /**
     * Remove a route from Route registry.
     *
     * @param string $path
     *
     * @return void
     */
    public function unregisterRoute(string $path): void {
        unset($this->routes[$path]);
    }

    /**
     * Returns a Route for the provided path.
     *
     * @return ?Route
     */
    public function getRoute(string $path): ?Route {
        return $this->routes[$path] ?? null;
    }

    /**
     * Returns a list of registered Routes.
     *
     * @return array
     */
    public function listRoutes(): array {
        return array_keys($this->routes);
    }

    /**
     * Set the default middlewares for the route registry.
     *
     * @param array $middlewares Array of middleware class names.
     *
     * @throws InvalidArgumentException If a middleware does not exist or does not extend Middleware.
     * @return RouteRegistry Updated RouteRegistry instance.
     */
    public function setDefaultMiddlewares(array $middlewares): RouteRegistry {
        foreach ($middlewares as $middleware) {
            if (!class_exists($middleware) || !is_subclass_of($middleware, Middleware::class)) {
                throw new InvalidArgumentException($middleware . ' must extend ' . Middleware::class . '!');
            }

            $this->defaultMiddlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Get the default middlewares registered in the route registry.
     *
     * @return array Array of default middlewares.
     */
    public function getDefaultMiddlewares(): array {
        return $this->defaultMiddlewares;
    }
}
