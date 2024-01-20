<?php

/**
 * This class is responsible for processing WebSocket messages by passing them through a stack of message controllers.
 * Each controller in the stack can perform specific operations on the incoming WebSocket Frame and pass it
 * on to the next controller in the chain until a final response Frame is generated.
 *
 * @package Framework\WebSocket
 * @copyright © Elar Must.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Framework\Container\ClassContainer;

class WebSocketMessageHandler implements WebSocketMessageHandlerInterface {
    /**
     * @param ClassContainer $classContainer Class container.
     * @param array $controllers List of controllers to process.
     */
    public function __construct(
        private ClassContainer $classContainer,
        private array $controllerStack = []
    ) {
    }

    /**
     * Process each controller in the stack and return the final Response.
     *
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Basic response Frame to work with.
     *
     * @return Frame Response Frame.
     */
    public function handle(Server $server, Frame $frame): Frame {
        if (empty($this->controllerStack)) {
            // Return response Frame, if there are no more controllers left.
            return $frame;
        }

        // Process the middleware
        $controller = array_shift($this->controllerStack);
        $controller = $this->classContainer->get($controller, useCache: false);
        return $controller->execute($server, $frame, $this);
    }
}
