<?php

/**
 * The ControllerInterface is used to define the contract for controllers that handle
 * specific routes.
 * Implementing classes should process, incoming requests, perform any necessary actions
 * and return a PSR-7 response.
 *
 * Copyright @ Elar Must.
 */

namespace Framework\Http;

use Psr\Http\Message\ResponseInterface;

interface ControllerInterface {
    /**
     * Process the controller and return a response.
     *
     * @return ResponseInterface
     */
    public function process(): ResponseInterface;
}
