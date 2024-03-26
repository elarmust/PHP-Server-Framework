<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Http\Events;

use Framework\Http\Route;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class BeforeMiddlewaresEvent implements StoppableEventInterface {
    private bool $stopped = false;

    public function __construct(
        private ServerRequestInterface $request,
        private ResponseInterface $response,
        private ?Route $route
    ) {
    }

    public function getRequest(): ServerRequestInterface {
        return $this->request;
    }

    public function getResponse(): ResponseInterface {
        return $this->response;
    }

    public function getRoute(): ?Route {
        return $this->route;
    }

    public function setRequest(ServerRequestInterface $request): void {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response): void {
        $this->response = $response;
    }

    public function setRoute(?Route $route): void {
        $this->route = $route;
    }

    public function stopEvent(): void {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
