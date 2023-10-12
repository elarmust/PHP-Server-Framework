<?php

namespace Framework\Layout\Controllers;

use Framework\View\ViewRegistry;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\AbstractRouteController;
use Framework\Http\ControllerStackInterface;
use Psr\Http\Message\ServerRequestInterface;

class BasicPage extends AbstractRouteController {
    public function __construct(private ViewRegistry $viewRegistry) {}

    public function execute(ServerRequestInterface $request, ResponseInterface $response, ControllerStackInterface $controllerStack): ResponseInterface {
        $view = $this->viewRegistry->getView('basicPage');
        $response->getBody()->write($view->getView());
        return $controllerStack->execute($request, $response);
    }
}
