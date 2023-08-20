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
use OpenSwoole\Core\Psr\Response;
use Framework\Core\ClassContainer;
use Framework\ViewManager\ViewManager;
use Psr\Http\Message\ResponseInterface;
use Framework\EventManager\EventManager;
use Psr\Http\Server\MiddlewareInterface;
use Framework\Http\RequestHandlerRegistry;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class HttpRouter implements RequestHandlerInterface {
    private ClassContainer $classContainer;
    private EventManager $eventManager;
    private RequestHandlerRegistry $requestHandlerRegistry;
    private ViewManager $viewManager;
    private Logger $logger;

    public function __construct(
        ClassContainer $classContainer,
        EventManager $eventManager,
        RequestHandlerRegistry $requestHandlerRegistry,
        ViewManager $viewManager,
        Logger $logger
    ) {
        $this->classContainer = $classContainer;
        $this->eventManager = $eventManager;
        $this->requestHandlerRegistry = $requestHandlerRegistry;
        $this->viewManager = $viewManager;
        $this->logger = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface {
        //$this->eventManager->dispatchEvent('beforePageLoad', ['request' => &$request]);
        $response = new Response('Not found', 200);

        $content = $this->viewManager->getView('EmptyView');

        $highestMatch = $this->findRouteNearestMatch($request->getServerParams()['path_info'], $this->requestHandlerRegistry->listHandledPaths());

        if ($highestMatch) {
            //foreach ($this->requestHandlerRegistry->registerHandler($highestMatch) as $routeHandler) {
            //}

            //$controller = $this->classContainer->get($routeHandler, cache: false);
            try {
                $response = $this->requestHandlerRegistry->getHandler($highestMatch)->handle($request);
                //if (!$controller->run($request, $response, $content)) {
                //    break;
                //};
            } catch (Throwable $e) {
                $this->logger->log(LogLevel::NOTICE, $e->getMessage(), identifier: 'framework');
                $this->logger->log(LogLevel::NOTICE, $e->getTraceAsString(), identifier: 'framework');
            }
        }

        //$this->eventManager->dispatchEvent('afterPageLoad', ['request' => &$request, 'response' => &$response]);
        return $response;
    }

    private function findRouteNearestMatch(string $requestedRoute, array $availableRoutes): string {
        $requestedRoute = explode('/', $requestedRoute);

        $routePartsMatched = [];
        foreach ($availableRoutes as $route) {
            $routeParts = explode('/', $route);
            $argsToSkip = [];

            $routePartsMatched[$route] = 0;
            foreach ($routeParts as $index => $routePart) {
                foreach ($requestedRoute as $index2 => $urlParam) {
                    if (in_array($index2, $argsToSkip)) {
                        continue;
                    }

                    // Check if registered route part matches url param of if route part is a wildcard
                    if (strtolower($routePart) == strtolower($urlParam) || $routePart == '%') {
                        $routePartsMatched[$route]++;
                        $argsToSkip[] = $index2;
                    } else {
                        if ($index2 < $index) {
                            $routePartsMatched[$route]--;
                        }
                    }
                }
            }

            if ($routePartsMatched[$route] < 1) {
                unset($routePartsMatched[$route]);
            }
        }

        $highestMatch = array_keys($routePartsMatched, max($routePartsMatched))[0] ?? null;

        if (count(array_unique($routePartsMatched, SORT_REGULAR)) === 1) {
            $urlParams = [];
            foreach ($routePartsMatched as $command => $matches) {
                $urlParams[$command] = explode('/', $command);
            }

            $highestMatch = array_keys($urlParams, min($urlParams))[0];
        }

        return $highestMatch;
    }
}