<?php

/**
 * The RouteControllerInterface is used to define the contract for controllers that handle
 * specific routes.
 * Implementing classes should process, incoming requests, perform any necessary actions
 * and return a PSR-7 response.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouteControllerInterface {
    /**
     * Process the controller and return a response.
     *
     * @return ResponseInterface
     */
    public function execute(ServerRequestInterface $request, ResponseInterface $response, ControllerStackInterface $controllerStack): ResponseInterface;
}
