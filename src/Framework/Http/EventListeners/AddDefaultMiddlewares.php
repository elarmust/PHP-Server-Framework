<?php

namespace Framework\Http\EventListeners;

use Framework\Event\EventListenerInterface;
use Framework\Http\Middlewares\SessionMiddleware;
use Framework\Http\Middlewares\ParseRequestMiddleware;

class AddDefaultMiddlewares implements EventListenerInterface {
    public function __invoke(object $event): void {
        $defaultMiddlewares = [
            ParseRequestMiddleware::class,
            SessionMiddleware::class,
        ];
        $route = $event->getRoute();
        // Get the middlewares from the route.
        $middlewares = $route->getMiddlewareStack();
        // Default middlewares are added to the beginning of the middleware stack.
        $route->setMiddlewareStack(array_merge($defaultMiddlewares, $middlewares));
        // Update the route.
        $event->setRoute($route);
    }
}
