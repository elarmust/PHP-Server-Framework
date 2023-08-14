<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\Core\ClassContainer;

class RouteRegister {
    public ClassContainer $classContainer;
    private array $routes;
    private Logger $logger;

    public function __construct(ClassContainer $classContainer, Logger $logger) {
        $this->classContainer = $classContainer;
        $this->logger = $logger;
    }

    public function registerRouteHandler(string $path, string $class): void {
        if (!is_subclass_of($class, RouteHandlerInterface::class)) {
            throw new InvalidArgumentException('Route handler \'' . $class . '\' must be an instance of ' . RouteHandlerInterface::class . '!');
        }

        $this->routes[$path][] = $class;
    }

    public function unregisterRouteHandler(string $path, string $class): void {
        if (!is_subclass_of($class, RouteHandlerInterface::class)) {
            throw new InvalidArgumentException('Route handler \'' . $class . '\' must be an instance of ' . RouteHandlerInterface::class . '!');
        }

        $key = array_search($class, $this->routes[$path]);
        if ($key === false) {
            $this->logger->log(Logger::NOTICE, 'Attempting to unregister route handler: \'' . $class . '\' for route \'' . $path . '\'.', identifier: 'framework');
            return;
        }

        unset($this->routes[$path][$key]);
    }

    public function unregisterRoute(string $path): void {
        if (!isset($this->routes[$path])) {
            $this->logger->log(Logger::NOTICE, 'Attempting to unregister route \'' . $path . '\'.', identifier: 'framework');
            return;
        }

        unset($this->routes[$path]);
    }

    public function getRoutes(): array {
        return array_keys($this->routes);
    }

    public function getRouteHandlers(string $path): array {
        return $this->routes[$path] ?? [];
    }
}