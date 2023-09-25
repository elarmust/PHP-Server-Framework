<?php

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Abstract class for basic Route Controller functions.
 */

class AbstractRouteController implements RouteControllerInterface {
    /**
     * Execture Route Controller.
     * 
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ControllerStackInterface $controllerStack): ResponseInterface {
        return $response;
    }
}
