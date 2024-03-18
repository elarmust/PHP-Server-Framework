<?php

namespace Framework\Utils;

class RouteUtils {
    public static function findNearestMatch(string $requestedRoute, array $availableRoutes, string $routeSeparator): ?string {
        $requestedRouteParts = explode($routeSeparator, ltrim($requestedRoute, $routeSeparator));
        array_unshift($requestedRouteParts, $routeSeparator);

        $matchedRoutes = [];
        foreach ($availableRoutes as $route) {
            $routeParts = explode($routeSeparator, ltrim($route, $routeSeparator));

            if (!$routeParts[0]) {
                $routeParts[0] = $routeSeparator;
            } else {
                array_unshift($routeParts, $routeSeparator);
            }

            $matchedCount = 0;
            foreach ($routeParts as $index => $routePart) {
            	if (str_starts_with($routePart, '?')) {
            		// Optional route parameters are always matched regardless if they are present in the requested route.
                    $matchedCount++;
            	} else if (isset($requestedRouteParts[$index]) && (strtolower($routePart) === strtolower($requestedRouteParts[$index]) || str_starts_with($routePart, '%'))) {
                    // Match exact route part or path parameter placeholder. Must be present in the requested route.
                    $matchedCount++;
                } else {
                    break;
                }
            }

            // Requested route is considered a match as long as all parts of the route are matched.
            if ($matchedCount === count($routeParts)) {
                $matchedRoutes[$route] = $matchedCount;
            }
        }

        // Sort the matched routes based on the number of matched parts in descending order
        arsort($matchedRoutes);

        // Return the route with the highest number of matched parts
        return key($matchedRoutes);
    }

    public static function getPathVariables(string $requestedRoute, string $matchedRoute, string $routeSeparator): array {
        $requestedRoute = explode($routeSeparator, ltrim($requestedRoute, $routeSeparator));
        $matchedRoute = explode($routeSeparator, ltrim($matchedRoute, $routeSeparator));

        $pathVariables = [];
        foreach ($matchedRoute as $index => $routePart) {
            // Match mandatory and optional path variables
            if (str_starts_with($routePart, '%') || str_starts_with($routePart, '?')) {
                $pathVariables[substr($routePart, 1)] = $requestedRoute[$index] ?? null;
            }
        }

        return $pathVariables;
    }
}
