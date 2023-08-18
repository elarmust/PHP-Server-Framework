<?php 

use Stream;
use Swoole\Http\Response;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

class ServerResponse implements ResponseInterface {
    private $swooleResponse;
    private StreamInterface|null $bodyContent;

    public function __construct(Response $swooleResponse) {
        $this->swooleResponse = $swooleResponse;
    }

    public function getProtocolVersion(): string {
        return '1.1'; // Change this as needed
    }

    public function withProtocolVersion($version): self {
        // You can implement this if needed
        return clone $this;
    }

    public function getHeaders(): array {
        return $this->swooleResponse->header;
    }

    public function hasHeader($name): bool {
        return isset($this->swooleResponse->header[$name]);
    }

    public function getHeader($name): array {
        return $this->swooleResponse->header[$name] ?? [];
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

    public function getStatusCode(): int {
        return $this->swooleResponse->getStatusCode();
    }

    public function withStatus($code, $reasonPhrase = ''): self {
        // You can implement this if needed
        return clone $this;
    }

    public function getReasonPhrase(): string {
        // You can implement this if needed
        return '';
    }

    public function getBody(): StreamInterface {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $this->bodyContent); // Assuming Swoole's getBody() returns a string
        rewind($stream);

        return new Stream($stream);
    }

    public function write(string $content): bool {
        $this->bodyContent .= $content; // Store the content as it's written
        return $this->swooleResponse->write($content);
    }

    public function withBody(StreamInterface $body): self {
        $response = new ServerResponse($this->swooleResponse);
        $response->swooleResponse->getBody()->clear(); // Clear the existing body
        $response->swooleResponse->write($swooleResponseBody);
        return $response;
    }
}