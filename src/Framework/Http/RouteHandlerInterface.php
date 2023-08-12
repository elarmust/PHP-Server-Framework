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
    public function run(Request &$request, Response &$response, ?View &$content);
}