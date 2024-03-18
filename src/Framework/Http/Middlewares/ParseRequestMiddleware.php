<?php

namespace Framework\Http\Middlewares;

use Framework\Http\Middleware;
use Framework\Utils\RouteUtils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParseRequestMiddleware extends Middleware {
    /**
     * Process the incoming request by parsing the request body and setting the parsed content as the request's parsed body.
     * Also sets the path parameters as an attribute in the request.
     *
     * @param ServerRequestInterface $request Incoming request.
     * @param RequestHandlerInterface $handlerRequest handler.
     *
     * @return ResponseInterface Response returned by the request handler.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $contentType = $request->getHeaderLine('Content-Type');

        if ($contentType === 'application/json') {
            $content = json_decode($request->getBody()->getContents(), true);
        } else if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($request->getBody()->getContents(), $content);
        } else if (str_contains($contentType, 'multipart/form-data')) {
            $content = $this->parseMultipartFormData($contentType, $request->getBody()->getContents());
        }

        $request = $request->withParsedBody($content ?? []);

        $pathParams = RouteUtils::getPathVariables($request->getServerParams()['path_info'], $this->getRoute()->getPath(), '/');

        foreach ($pathParams as $param) {
            $param = ($param === null) ? $param : urldecode($param );
        }

        $request = $request->withAttribute('pathParams', $pathParams);

        return $handler->handle($request);
    }

    /**
     * Parses the multipart form data from the given content type and body.
     *
     * @param string $contentType Request content type.
     * @param string $body Request body.
     *
     * @return array Parsed form data as an associative array.
     */
    private function parseMultipartFormData(string $contentType, string $body): array {
        $formData = [];
        $boundary = explode('boundary=', $contentType)[1] ?? null;

        if (!$boundary) {
            return [];
        }

        $body = "\r\n" . $body;
        // Split body into parts using boundary
        $parts = explode("\r\n--" . $boundary, $body);
        // Remove first and last empty parts
        array_shift($parts);
        array_pop($parts);

        // Parse each part
        foreach ($parts as $part) {
            // Find headers and content of the part
            list($rawHeaders, $content) = explode("\r\n\r\n", $part, 2);

            // Parse headers into associative array
            $headers = [];
            foreach (explode("\r\n", $rawHeaders) as $header) {
                if (!$header) {
                    continue;
                }

                list($name, $value) = explode(': ', $header);
                $headers[$name] = $value;
            }

            // Check if the part is a form field
            if (isset($headers['Content-Disposition']) && strpos($headers['Content-Disposition'], 'form-data') !== false) {
                // Ignore file fields. Files are already handled by OpenSwoole.
                if (strpos($headers['Content-Disposition'], 'filename') !== false) {
                    continue;
                }

                // Extract field name and value
                preg_match('/name="([^"]+)"/', $headers['Content-Disposition'], $matches);
                if (!isset($matches[1])) {
                    continue;
                }

                // Store field value in the result array
                $formData[$matches[1]] = $content;
            }
        }

        return $formData;
    }
}
