<?php

namespace Framework\Http;

use Exception;
use InvalidArgumentException;
use OpenSwoole\Core\Psr\Stream;
use Framework\Http\Mime\MimeTypes;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;
use OpenSwoole\Core\Psr\Response as OpenSwooleResponse;

class Response extends OpenSwooleResponse implements ResponseInterface {
    public function __construct(protected MimeTypes $mimeTypes, StreamInterface|string $body, int $statusCode = 200, string $reasonPhrase = '', array $headers = [], string $protocolVersion = '1.1') {
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
            $new->body = Stream::streamFor($stringOrStream);
        } else if ($stringOrStream instanceof StreamInterface) {
            $new->body = $stringOrStream;
        } else {
            throw new InvalidArgumentException('Invalid body type: ' . gettype($stringOrStream));
        }

        return $new;
    }

    /**
     * Set the response body to a file.
     *
     * @param string $fileRoot Path to the directory containing the file path. This is used to access control.
     * @param string $filePath Path to the file to send. Must be within the file root directory.
     * @param bool $asAttachment Whether to send the file as an attachment or body content.
     *
     * @return ResponseInterface Response with the file as the body.
     */
    public function withFile(string $fileRoot, string $filePath, bool $asAttachment = false): ResponseInterface {
        $realPath = realpath($fileRoot . $filePath);

        if (!$realPath || !file_exists($realPath) || !str_starts_with($realPath, $fileRoot)) {
            $new = clone $this;
            $new->body = Stream::streamFor('');
            $new = $new->withStatus(404);
            return $new;
        }

        try {
            $new = clone $this;
            $new->body = Stream::createStreamFromFile($realPath);
            $new = $new->withStatus(200);
            $new = $new->withHeader('Content-Type', $this->mimeTypes->guessType($realPath));

            if ($asAttachment) {
                // Send the file as an attachment.
                $new = $new->withHeader('Content-Disposition', 'attachment; filename="' . basename($realPath) .'"');
            }

            return $new;
        } catch (Exception $e) {
            $new = clone $this;
            $new->body = Stream::streamFor('');
            $new = $new->withStatus(404);
        }

        return $new;
    }    
}
