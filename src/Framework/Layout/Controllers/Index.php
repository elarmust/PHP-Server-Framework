<?php

namespace Framework\Layout\Controllers;

use Framework\Http\RouteHandlerInterface;
use Framework\ViewManager\View;
use Framework\ViewManager\ViewManager;
use Swoole\Http\Request;
use Swoole\Http\Response;

class Index implements RouteHandlerInterface {
    private ViewManager $viewManager;

    public function __construct(ViewManager $viewManager) {
        $this->viewManager = $viewManager;
    }

    public function run(Request &$request, Response &$response, ?View &$content): bool {
        $content = $this->viewManager->parseView($this->viewManager->getView('BasicPage'));
        return true;
    }
}