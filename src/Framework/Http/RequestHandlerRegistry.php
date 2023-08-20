<?php

/**
 * Contains a list of RequestHandlerInterface request handlers
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerRegistry {
    private array $handlers = [];

    /**
     * Register a RequestHandlerInterface handler for a request path.
     * 
     * @param string @path
     * @param RequestHandlerInterface $handler
     * @return void
     */
    public function registerHandler(string $path, RequestHandlerInterface $handler): void {
        $this->handlers[$path] = $handler;
    }

    /**
     * Unregister a handler from a path.
     * 
     * @param string @path
     * @return void
     */
    public function unregisterHandler(string $path): void {
        unset($this->handlers[$path]);
    }

    /**
     * Returns a RequestHandlerInterface handler for a given path.
     * 
     * @param string @path
     * @return ?RequestHandlerInterface
     */
    public function getHandler(string $path): ?RequestHandlerInterface {
        return $this->handlers[$path] ?? null;
    }

    /**
     * Returns a list of paths for which there are RequestHandlerInterface handlers registered.
     * 
     * @return array
     */
    public function listHandledPaths(): array {
        return array_keys($this->handlers);
    }
}