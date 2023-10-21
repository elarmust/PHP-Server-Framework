<?php

/**
 * Registry for RequestHandlers, Controllers, Middlewares and their associated routes.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Http;

use Framework\Http\Route;

class RouteRegistry {
    private array $routes = [];

    /**
     * Register a new Route.
     *
     * @param string $path
     * @param string $requestHandler
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
}
