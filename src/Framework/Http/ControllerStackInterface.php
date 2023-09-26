<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerStackInterface extends MiddlewareInterface {
    /**
     * Process the controller stack and return a response.
     * 
     * @param ServerRequestInterface $request The incoming Request to the server.
     * @param ResponseInterface $response The response returned by Middlewares.
     *
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
