<?php

namespace Framework\Layout\Controllers;

use Framework\Http\ControllerStackInterface;
use Framework\Http\AbstractRouteController;
use Framework\View\ViewRegistry;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class BasicPage extends AbstractRouteController {
    public function __construct(private ViewRegistry $viewRegistry) {}

    public function execute(ServerRequestInterface $request, ResponseInterface $response, ControllerStackInterface $controllerStack): ResponseInterface {
        $view = $this->viewRegistry->getView('basicPage');
        $response = $response->withStatus(200);
        $response->getBody()->write($view->getView());
        return $controllerStack->execute($request, $response);
    }
}
