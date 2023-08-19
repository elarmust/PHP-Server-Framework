<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FirstMiddleware3 implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        // Continue with the existing response
        $response = $handler->handle($request);
        $body = $response->getBody();
        $body->write('<h1>PSR-15 Middleware Example</h1>');

        return $response->withBody($body);
    }
}