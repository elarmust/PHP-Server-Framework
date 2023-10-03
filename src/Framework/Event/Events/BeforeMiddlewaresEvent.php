<?php

/**
 * @copyright  WereWolf Labs OÜ
 */

namespace Framework\Event\Events;

use Framework\Http\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class BeforeMiddlewaresEvent implements StoppableEventInterface {
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private Route $route;
    private bool $stopped = false;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response, Route $route) {
        $this->request = $request;
        $this->response = $response;
        $this->route = $route;
    }

    public function getRequest(): ServerRequestInterface {
        return $this->request;
    }

    public function getResponse(): ResponseInterface {
        return $this->response;
    }

    public function getRoute(): Route {
        return $this->route;
    }

    public function stopEvent(): void {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
