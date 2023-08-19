<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FirstMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        // Modify the shared data
        $data = $request->getAttribute('shared_data', []);
        $data['message'] = 'Hello from Middleware1!';
        $request = $request->withAttribute('shared_data', $data);

        return $handler->handle($request);
    }
}