<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouteControllerInterface {
    /**
     * Process the route and return a response.
     *
     * @return void
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}