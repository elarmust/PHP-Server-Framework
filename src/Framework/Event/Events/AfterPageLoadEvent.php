<?php

/**
 * @copyright  WereWolf Labs OÜ
 */

namespace Framework\Event\Events;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class AfterPageLoadEvent implements StoppableEventInterface {
    private ServerRequestInterface $request;
    private ResponseInterface $response;
    private bool $stopped = false;

    public function __construct(ServerRequestInterface $request, ResponseInterface $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function getRequest(): ServerRequestInterface {
        return $this->request;
    }

    public function getResponse(): ResponseInterface {
        return $this->response;
    }

    public function setRequest(ServerRequestInterface $request): void {
        $this->request = $request;
    }

    public function setResponse(ResponseInterface $response): void {
        $this->response = $response;
    }

    public function stopEvent(): void {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
