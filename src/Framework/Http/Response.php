<?php

namespace Framework\Http;

use InvalidArgumentException;
use OpenSwoole\Core\Psr\Stream;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;

class Response extends OpenSwooleResponse implements ResponseInterface {
    protected string $streamType = 'php://memory';

    public function __construct(StreamInterface|string $body, int $statusCode = 200, string $reasonPhrase = '', array $headers = [], string $protocolVersion = '1.1') {
        parent::__construct($body, $statusCode, $reasonPhrase, $headers, $protocolVersion);
    }

    /**
     * Get the response body.
     *
     * @return StreamInterface|string Body as a StreamInterface or string.
     * New stream will be created if the body is a string.
     */
    public function withBody($stringOrStream): ResponseInterface {
        $new = clone $this;
        if (is_string($stringOrStream)) {
            $new->stream = Stream::streamFor($stringOrStream);
        } else if ($stringOrStream instanceof StreamInterface) {
            $new->stream = $stringOrStream;
        } else {
            throw new InvalidArgumentException('Invalid body type: ' . gettype($stringOrStream));
        }

        return $new;
    }

    public function getStream(): StreamInterface {
        return $this->stream;
    }
}
