<?php

namespace Framework\Http;

use Framework\Utils\RouteUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerMiddleware implements MiddlewareInterface {
    private RouteRegistry $routeRegistry;

    public function __construct(RouteRegistry $routeRegistry) {
        $this->routeRegistry = $routeRegistry;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $path = $request->getUri()->getPath();

        $response = $handler->handle($request);
        $route = $this->routeRegistry->getRoute(RouteUtils::findNearestMatch($path, $this->routeRegistry->listRoutes(), '/'));

        if ($route) {
            foreach ($route->getControllers() as $controller) {
                $response = $controller->execute($request, $response);
            }
        }

        return $response;
    }
}
