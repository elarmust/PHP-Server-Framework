<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Framework\ViewManager\View;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;

interface RouteHandlerInterface {
    /**
     * @param Request &$request
     * @param Response &$response
     * @param ?View &$content
     * @return ?bool Whether to continue processing matched route path or not.
     */
    public function run(Request &$request, Response &$response, ?View &$content): bool;
}