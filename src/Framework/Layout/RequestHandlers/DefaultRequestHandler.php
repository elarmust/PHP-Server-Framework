<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DefaultRequestHandler implements RequestHandlerInterface {

    public function handle(ServerRequestInterface $request): ResponseInterface {
        // Create a default response
        $response = new Psr7Response(new Response());
        $response->getBody()->write("Original Response\n");
        return $response;
    }
}
