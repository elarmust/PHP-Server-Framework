<?php

/**
 * Http router.
 * Route http requests to registered route handlers.
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Throwable;
use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use Framework\Utils\RouteUtils;
use OpenSwoole\Core\Psr\Response;
use Framework\Core\ClassContainer;
use Psr\Http\Message\ResponseInterface;
use Framework\EventManager\EventManager;
use Framework\Http\RouteRegistry;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpRouter implements RequestHandlerInterface {
    private ClassContainer $classContainer;
    private EventManager $eventManager;
    private RouteRegistry $routeRegistry;
    private Logger $logger;
    private ControllerMiddleware $controllerMiddleware;

    public function __construct(
        ClassContainer $classContainer,
        EventManager $eventManager,
        RouteRegistry $routeRegistry,
        Logger $logger,
        ControllerMiddleware $controllerMiddleware
    ) {
        $this->classContainer = $classContainer;
        $this->eventManager = $eventManager;
        $this->routeRegistry = $routeRegistry;
        $this->logger = $logger;
        $this->controllerMiddleware = $controllerMiddleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        $response = new Response('', 200);
        $highestMatch = RouteUtils::findNearestMatch($request->getServerParams()['path_info'], $this->routeRegistry->listRoutes(), '/');

        if ($highestMatch) {
            try {
                $route = clone $this->routeRegistry->getRoute($highestMatch);
                $route->addMiddlewares([$this->controllerMiddleware]);
                $this->eventManager->dispatchEvent('beforePageLoad', ['request' => &$request, 'response' => &$response, 'route' => &$route]);
                $requestHandler = $this->classContainer->get($route->getRequestHandler(), [$route->getMiddlewareStack()], cache: false);
                $response = $requestHandler->handle($request);
            } catch (Throwable $e) {
                $this->logger->log(LogLevel::NOTICE, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::NOTICE, $e->getTraceAsString(), identifier: 'framework');
            }
        }

        $this->eventManager->dispatchEvent('afterPageLoad', ['request' => &$request, 'response' => &$response]);
        return $response;
    }
}