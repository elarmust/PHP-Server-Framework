<?php

namespace Framework\Http;

use Framework\Http\Route;
use Framework\Http\Response;
use Framework\Utils\RouteUtils;
use Psr\Http\Message\ResponseInterface;
use Framework\Http\ControllerStackInterface;

/**
 * Abstract class for basic Route Controller functions.
 */

class Controller implements ControllerInterface {
    protected array $subRoutes = [];

    public function __construct(
        protected Route $route,
        protected Request $request,
        protected Response $response,
        protected ControllerStackInterface $controllerStack
    ) {
    }

    /**
     * Entry point for the controller.
     * Define the logic for the controller here.
     *
     * @return ResponseInterface Response returned by the result of the controller stack execution.
     */
    public function process(): ResponseInterface {
        return $this->controllerStack->next($this->request, $this->response);
    }

    /**
     * Route a GET request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function get(string $path, callable $function): void {
        $this->subRoutes['GET'][$path] = $function;
    }

    /**
     * Route a POST request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function post(string $path, callable $function): void {
        $this->subRoutes['POST'][$path] = $function;
    }

    /**
     * Route a PUT request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function put(string $path, callable $function): void {
        $this->subRoutes['PUT'][$path] = $function;
    }

    /**
     * Route a PATCH request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function patch(string $path, callable $function): void {
        $this->subRoutes['PATCH'][$path] = $function;
    }

    /**
     * Route a DELETE request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function delete(string $path, callable $function): void {
        $this->subRoutes['DELETE'][$path] = $function;
    }

    /**
     * Route a OPTIONS request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function options(string $path, callable $function): void {
        $this->subRoutes['OPTIONS'][$path] = $function;
    }

    /**
     * Route a HEAD request to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function head(string $path, callable $function): void {
        $this->subRoutes['OPTIONS'][$path] = $function;
    }

    /**
     * Route any request method to a function.
     *
     * @param string $path Path to match.
     * @param callable $function Function to be executed when the route is matched.
     * E.g should request path "/path1/path2" match "/path1".
     *
     * @return void
     */
    public function any(string $path, callable $function): void {
        $this->subRoutes['ANY'][$path] = $function;
    }

    /**
     * Route a request to a function.
     *
     * @return ResponseInterface Response returned by the function.
     */
    protected function routeToFunction(): ResponseInterface {
        $method = $this->request->getMethod();
        $availableRoutes = array_merge($this->subRoutes[$method] ?? [], $this->subRoutes['ANY'] ?? []);
        $match = RouteUtils::findNearestMatch($this->request->getRequestTarget(), array_keys($availableRoutes), '/');

        if (!$match) {
            return $this->response;
        }

        $pathParams = RouteUtils::getPathVariables($this->request->getRequestTarget(), $match, '/');
        foreach ($pathParams as &$param) {
            $param = ($param === null) ? $param : urldecode($param );
        }

        $this->request = $this->request->withAttribute('pathParams', array_merge($this->request->pathParam(), $pathParams));

        return $availableRoutes[$match]();
    }
}
