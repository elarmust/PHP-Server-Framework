<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Framework\ViewManager\View;
use Swoole\Http\Request;
use Swoole\Http\Response;

interface RouteHandlerInterface {
    /**
     * @param Request &$request
     * @param Response &$response
     * @param ?View &$content
     * @return ?bool Whether to continue processing matched route path or not.
     */
    public function run(Request &$request, Response &$response, ?View &$content): bool;
}