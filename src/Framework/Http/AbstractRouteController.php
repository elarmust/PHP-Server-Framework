<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract class for Route Controller functions.
 */

class AbstractRouteController implements RouteControllerInterface {
    /**
     * Execture Route Controller.
     * 
     * @return void
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        return $response;
    }
}
