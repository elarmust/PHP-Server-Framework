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
use Framework\Event\Events\BeforePageLoadEvent;
use Framework\Event\Events\AfterPageLoadEvent;
use Framework\Event\EventDispatcher;
use Framework\Core\ClassContainer;
use Framework\Event\Events\BeforeMiddlewaresEvent;
use Framework\Http\RouteRegistry;
use Framework\Utils\RouteUtils;
use Framework\Logger\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LogLevel;
use OpenSwoole\Core\Psr\Response;

class HttpRouter {
    private ClassContainer $classContainer;
    private EventDispatcher $EventDispatcher;
    private RouteRegistry $routeRegistry;
    private Logger $logger;

    /**
     * @param ClassContainer $classContainer
     * @param EventDispatcher $EventDispatcher
     * @param RouteRegistry $routeRegistry
     * @param Logger $logger
     */
    public function __construct(
        ClassContainer $classContainer,
        EventDispatcher $EventDispatcher,
        RouteRegistry $routeRegistry,
        Logger $logger
    ) {
        $this->classContainer = $classContainer;
        $this->EventDispatcher = $EventDispatcher;
        $this->routeRegistry = $routeRegistry;
        $this->logger = $logger;
    }

    /**
     * Processes an incoming HTTP request and generates an HTTP response.
     *
     * This method handles the following steps:
     * 1. Dispatches a "BeforePageLoadEvent" event to allow pre-processing of the request and response.
     * 2. Finds the nearest route match for the request's path.
     * 3. Dispatches a "BeforeMiddlewaresEvent" event for the matched route.
     * 4. Instantiates and executes the request handler associated with the route.
     * 5. Dispatches an "AfterPageLoadEvent" event to allow post-processing of the request and response.
     *
     * @param ServerRequestInterface $request The incoming HTTP request.
     *
     * @return ResponseInterface The HTTP response generated as a result of processing the request.
     */
    public function process(ServerRequestInterface $request): ResponseInterface {
        $response = new Response('', 404);
        $event = $this->EventDispatcher->dispatch(new BeforePageLoadEvent($request, $response));
        $request = $event->getRequest();
        $response = $event->getResponse();
        $highestMatch = RouteUtils::findNearestMatch($request->getServerParams()['path_info'], $this->routeRegistry->listRoutes(), '/');

        if ($highestMatch) {
            try {
                $route = clone $this->routeRegistry->getRoute($highestMatch);
                $this->EventDispatcher->dispatch(new BeforeMiddlewaresEvent($request, $response, $route));

                // Get a new RequestHandler instance for this route and handle it.
                $requestHandler = $this->classContainer->get($route->getRequestHandler(), [$route], cache: false);
                $response = $requestHandler->handle($request);
            } catch (Throwable $e) {
                $this->logger->log(LogLevel::NOTICE, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::NOTICE, $e->getTraceAsString(), identifier: 'framework');
            }
        }

        $event = $this->EventDispatcher->dispatch(new AfterPageLoadEvent($request, $response));
        return $event->getResponse();
    }
}
