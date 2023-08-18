<?php

use Swoole\Http\Request;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServerRequest implements ServerRequestInterface {

    private $swooleRequest;

    public function __construct(Request $swooleRequest) {
        $this->swooleRequest = $swooleRequest;
    }

    public function getProtocolVersion(): string {
        return '1.1'; // Change this as needed
    }

    public function withProtocolVersion($version): self {
        // You can implement this if needed
        return clone $this;
    }

    public function getHeaders(): array {
        return $this->swooleRequest->header;
    }

    public function hasHeader($name): bool {
        return isset($this->swooleRequest->header[$name]);
    }

    public function getHeader($name): array {
        return $this->swooleRequest->header[$name] ?? [];
    }

    public function getHeaderLine($name): string {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader($name, $value): self {
        // You can implement this if needed
        return clone $this;
    }

    public function withAddedHeader($name, $value): self {
        // You can implement this if needed
        return clone $this;
    }

    public function withoutHeader($name): self {
        // You can implement this if needed
        return clone $this;
    }

    public function getMethod(): string {
        return $this->swooleRequest->server['request_method'];
    }

    public function getUri(): UriInterface {
        return new Uri($this->swooleRequest->server['request_uri']);
    }

    public function getRequestTarget(): string {
        return $this->getUri()->getPath();
    }

    public function withRequestTarget($requestTarget): self {
        // You can implement this if needed
        return clone $this;
    }

    public function getBody(): StreamInterface {
        // You need to implement a PSR-7 StreamInterface for the body
    }
}
