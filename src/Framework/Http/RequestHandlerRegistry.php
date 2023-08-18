<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Psr\Http\Server\RequestHandlerInterface;

class HttpHandlerRegistry {
    private array $handlers;

    public function __construct() {
    }

    public function registerHandler(string $path, RequestHandlerInterface $handler): void {
        $this->handlers[$path][$handler::class] = $handler;
    }

    public function unregisterHandler(string $path, RequestHandlerInterface $handler): void {
        unset($this->handlers[$path][$handler::class]);
        if (empty($this->handlers[$path] ?? [1])) {
            unset($this->handlers[$path]);
        }
    }

    public function getHandlers(string $path): array {
        return $this->handlers[$path] ?? [];
    }
}