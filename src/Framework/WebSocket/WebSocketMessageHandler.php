<?php

/**
 * This class is responsible for processing WebSocket messages by passing them through a stack of message controllers.
 * Each controller in the stack can perform specific operations on the incoming WebSocket Frame and pass it
 * on to the next controller in the chain until a final response Frame is generated.
 *
 * @package Framework\WebSocket
 * @copyright © WereWolf Labs OÜ.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Framework\Core\ClassContainer;

class WebSocketMessageHandler implements WebSocketMessageHandlerInterface {
    private ClassContainer $classContainer;
    private array $controllerStack = [];

    /**
     * @param ClassContainer $classContainer Class container.
     * @param array $controllers List of controllers to process.
     */
    public function __construct(ClassContainer $classContainer, array $controllers) {
        $this->classContainer = $classContainer;
        $this->controllerStack = $controllers;
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
        $controller = $this->classContainer->get($controller, cache: false);
        return $controller->execute($server, $frame, $this);
    }
}
