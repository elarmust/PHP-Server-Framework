<?php

namespace Framework\Layout\Controllers;

use Framework\View\ViewRegistry;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\AbstractRouteController;
use Psr\Http\Message\ServerRequestInterface;

class BasicPage extends AbstractRouteController {
    private ViewRegistry $viewRegistry;

    public function __construct(ViewRegistry $viewRegistry) {
        $this->viewRegistry = $viewRegistry;
    }

    public function execute(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
        $response->getBody()->write($this->viewRegistry->getView('BasicPage')->getView());
        return $response;
    }
}