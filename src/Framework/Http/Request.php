<?php

namespace Framework\Http;

use OpenSwoole\Http\Request as HttpRequest;
use OpenSwoole\Core\Psr\Stream;
use OpenSwoole\Core\Psr\UploadedFile;
use OpenSwoole\Core\Psr\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest implements ServerRequestInterface {
    /**
     * Retrieve the value(s) of the specified request body parameter(s).
     * A wrapper for getParsedBody().
     *
     * @param string|array $query Request body parameter(s) to retrieve.
     * If an array is provided, an array of corresponding values will be returned.
     * If a string is provided, the value of the specified parameter will be returned.
     *
     * @return mixed The value(s) of the specified request body parameter(s). If the parameter(s) do not exist, then null will be returned.
     */
    public function params(string|array $query = []): mixed {
        if (is_array($query)) {
            if (!$query) {
                return $this->getParsedBody();
            }

            return array_map(fn($param) => $this->getParsedBody()[$param] ?? null, $query);
        }

        return $this->getParsedBody()[$query] ?? null;
    }

    /**
     * Retrieve the value(s) of the specified request query parameter(s).
     * A wrapper for getQueryParams().
     *
     * @param string|array $query Query parameter(s) to retrieve.
     * If an array is provided, an array of corresponding values will be returned.
     * If a string is provided, the value of the specified query parameter will be returned.
     *
     * @return mixed The value(s) of the specified query parameter(s). If the query parameter(s) do not exist, then null will be returned.
     */
    public function query(string|array $query = []): mixed {
        if (is_array($query)) {
            if (!$query) {
                return $this->getQueryParams();
            }

            return array_map(fn($param) => $this->getQueryParams()[$param] ?? null, $query);
        }

        return $this->getQueryParams()[$query] ?? null;
    }

    /**
     * Retrieve the value(s) of the specified path parameter(s).
     * A wrapper for getAttributes()['pathParams'].
     *
     * @param string|array $query Path parameter(s) to retrieve.
     * If an array is provided, an array of corresponding values will be returned.
     * If a string is provided, the value of the specified path parameter will be returned.
     *
     * @return mixed The value(s) of the specified path parameter(s). If the path parameter(s) do not exist, then null will be returned.
     */
    public function pathParam(string|array $query = []) {
        if (is_array($query)) {
            if (!$query) {
                return $this->getAttributes()['pathParams'] ?? [];
            }

            return array_map(fn($param) => $this->getAttributes()['pathParams'][$param] ?? null, $query);
        }

        return $this->getAttributes()['pathParams'][$query] ?? null;
    }

    /**
     * Create a new ServerRequestInterface compatible Frameowork\Http\Request from OpenSwoole\Http\Request.
     *
     * @param OpenSwoole\Http\Request $request Request object to create the Request from.
     *
     * @return Frameowork\Http\Request New request object.
     */
    public static function from(HttpRequest $request): Request {
        $files = [];

        if (isset($request->files)) {
            foreach ($request->files as $name => $fileData) {
                $files[$name] = new UploadedFile(
                    Stream::createStreamFromFile($fileData['tmp_name']),
                    $fileData['size'],
                    $fileData['error'],
                    $fileData['name'],
                    $fileData['type']
                );
            }
        }

        return new Request(
            $request->server['request_uri'],
            $request->server['request_method'],
            $request->rawContent() ? $request->rawContent() : 'php://memory',
            $request->header,
            isset($request->cookie) ? $request->cookie : [],
            isset($request->get) ? $request->get : [],
            $request->server,
            $files,
        );
    }
}
