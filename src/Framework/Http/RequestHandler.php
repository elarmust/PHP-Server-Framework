<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use OpenSwoole\Core\Psr\Response;

class RequestHandler implements RequestHandlerInterface {
    private array $middlewareStack = [];
    private Response $response;

    public function __construct(array $middlewareStack) {
        $this->middlewareStack = $middlewareStack;
        // Initialize class with empty response.
        $this->response = new Response('');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        if (empty($this->middlewareStack)) {
            // Return response, if there are no more middleware left.
            return $this->response;
        }

        // Process the middleware
        $this->response = array_shift($this->middlewareStack)->process($request, $this);
        return $this->response;
    }
}
