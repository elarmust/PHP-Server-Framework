<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FirstMiddleware2 implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        // Check shared data for modifications
        $data = $request->getAttribute('shared_data', []);

        if (isset($data['message'])) {
            // Modify the output based on shared data
            $response = $handler->handle($request);
            $body = $response->getBody();
            $body->write('<p>' . $data['message'] . '</p>');
            return $response->withBody($body);
        }

        return $handler->handle($request);
    }
}