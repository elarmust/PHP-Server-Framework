<?php

namespace Framework\Utils;

class RouteUtils {
    public static function findNearestMatch(string $requestedRoute, array $availableRoutes, string $routeSeparator): ?string {
        $requestedRoute = explode($routeSeparator, $requestedRoute);

        $routePartsMatched = [];
        foreach ($availableRoutes as $route) {
            $routeParts = explode($routeSeparator, $route);
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
                $urlParams[$command] = explode($routeSeparator, $command);
            }

            $highestMatch = array_keys($urlParams, min($urlParams))[0];
        }

        return $highestMatch ?? null;
    }
}
