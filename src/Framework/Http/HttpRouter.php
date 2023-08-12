<?php

/**
 * Http router.
 * Route http requests to registered route handlers.
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use Framework\Logger\Logger;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Framework\Core\ClassManager;
use Framework\Http\RouteRegister;
use Framework\ViewManager\ViewManager;
use Framework\EventManager\EventManager;
use Throwable;

class HttpRouter {
    private ClassManager $classManager;
    private EventManager $eventManager;
    private RouteRegister $routeRegister;
    private ViewManager $viewManager;
    private Logger $logger;

    public function __construct(
        ClassManager $classManager,
        EventManager $eventManager,
        RouteRegister $routeRegister,
        ViewManager $viewManager,
        Logger $logger
    ) {
        $this->classManager = $classManager;
        $this->eventManager = $eventManager;
        $this->routeRegister = $routeRegister;
        $this->viewManager = $viewManager;
        $this->logger = $logger;
    }

    public function parseRequest(Request $request, Response $response): string {
        $content = $this->viewManager->getView('EmptyView');
        $this->eventManager->dispatchEvent('beforePageLoad', ['request' => &$request, 'response' => &$response, 'content' => &$content]);

        $urlPath = $request->server['request_uri'];
        $routePartsMatched = [];
        foreach ($this->routeRegister->getRoutes() as $route) {
            $routeParts = explode('/', $route);
            $argsToSkip = [];

            $routePartsMatched[$route] = 0;
            foreach ($routeParts as $index => $routePart) {
                foreach ($urlPath as $index2 => $urlParam) {
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

        if ($highestMatch) {
            foreach ($this->routeRegister->getRouteHandlers($highestMatch) as $routeHandler) {
                $controller = $this->classManager->getTransientClass($routeHandler);
                try {
                    if (!$controller->run($request, $response, $content)) {
                        break;
                    };
                } catch (Throwable $e) {
                    $this->logger->log(Logger::LOG_ERR, $e->getMessage(), 'framework');
                    $this->logger->log(Logger::LOG_ERR, $e->getTraceAsString(), 'framework');
                }
            }
        }

        $this->eventManager->dispatchEvent('afterPageLoad', ['request' => &$request, 'response' => &$response, 'content' => &$content]);
        return $content->getView();
    }
}