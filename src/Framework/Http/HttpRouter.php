<?php

/**
 * HttpRouter class is responsible for routing incoming HTTP requests to the
 * appropriate request handlers based on the defined routes.
 * It serves as the entry point for processing incoming requests within a web application.
 * 
 * Copyright © WereWolf Labs OÜ.
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

class HttpRouter {
    private ClassContainer $classContainer;
    private EventManager $eventManager;
    private RouteRegistry $routeRegistry;
    private Logger $logger;

    public function __construct(
        ClassContainer $classContainer,
        EventManager $eventManager,
        RouteRegistry $routeRegistry,
        Logger $logger
    ) {
        $this->classContainer = $classContainer;
        $this->eventManager = $eventManager;
        $this->routeRegistry = $routeRegistry;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request): ResponseInterface {
        $this->eventManager->dispatchEvent('beforePageLoad', ['request' => &$request]);
        $response = new Response('', 404);
        $highestMatch = RouteUtils::findNearestMatch($request->getServerParams()['path_info'], $this->routeRegistry->listRoutes(), '/');

        if ($highestMatch) {
            try {
                $route = clone $this->routeRegistry->getRoute($highestMatch);
                $this->eventManager->dispatchEvent('beforeMiddlewares', ['request' => &$request, 'response' => &$response, 'route' => &$route]);
                // Get a new RequestHandler instance for this route and handle it.
                $requestHandler = $this->classContainer->get($route->getRequestHandler(), [$route], cache: false);
                $response = $requestHandler->handle($request);
            } catch (Throwable $e) {
                $this->logger->log(LogLevel::NOTICE, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::NOTICE, $e->getTraceAsString(), identifier: 'framework');
            }
        }

        $event = $this->eventManager->dispatchEvent('afterPageLoad', ['request' => &$request, 'response' => &$response]);
        return $event->getData()['response'];
    }
}