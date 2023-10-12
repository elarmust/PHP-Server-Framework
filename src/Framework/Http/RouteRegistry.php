<?php

/**
 * Registry for RequestHandlers, Controllers, Middlewares and their associated routes.
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Framework\Http\Route;
use Framework\Core\ClassContainer;

class RouteRegistry {
    private array $routes = [];

    public function __construct(private ClassContainer $classContainer) {}

    /**
     * Register a new Route.
     * 
     * @param string $path
     * @param string $requestHandler
     * @return Route
     */
    public function registerRoute(string $path, string $requestHandler): Route {
        $newRoute = $this->classContainer->get(Route::class, [$path, $requestHandler], cache: false);
        $this->routes[$path] = $newRoute;
        return $newRoute;
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
