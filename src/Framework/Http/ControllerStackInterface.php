<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ControllerStackInterface {
    /**
     * Process the the nextcontroller in the stack and return a response.
     *
     * @param ServerRequestInterface $request Incoming Request to the server.
     * @param ResponseInterface $response Response returned by Middlewares.
     *
     * @return ResponseInterface
     */
    public function next(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}
