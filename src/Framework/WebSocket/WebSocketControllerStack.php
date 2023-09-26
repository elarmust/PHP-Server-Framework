<?php

/**
 * Copyright © WereWolf Labs OÜ.
 */

namespace Framework\WebSocket;

use InvalidArgumentException;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Framework\Http\WebSocketControllerInterface;

class WebSocketControllerStack {
    private array $controllerStack = [];

    /**
     * Add WebSocketControllerInterface compatible controllers to the WebSocket controller stack.
     * 
     * @param array $controllers An array of WebSocketControllerInterface compatible controllers.
     * @return WebSocketControllerStack
     */
    public function addControllers(array $controllers): WebSocketControllerStack {
        foreach ($controllers as $controller) {
            if (!is_object($controller)) {
                throw new InvalidArgumentException($controller . ' must be an Object, which implements ' . WebSocketControllerInterface::class .  '!');
            }

            if (!$controller instanceof WebSocketControllerInterface) {
                throw new InvalidArgumentException($controller::class . ' must implement ' . WebSocketControllerInterface::class . '!');
            }

            $this->controllerStack[$controller::class] = $controller;
        }

        return $this;
    }

    /**
     * Remove a controller from WebSocket controller stack.
     * 
     * @param array $controllerClassNames An array of controller class names to to remove.
     * @return WebSocketControllerStack
     */
    public function removeControllers(array $controllerClassNames): WebSocketControllerStack {
        foreach ($controllerClassNames as $controller) {
            unset($this->controllerStack[$controller]);
        }

        return $this;
    }

    /**
     * Replace the WebSocket controller stack controllers.
     * 
     * @param array $controllers An array of WebSocketControllerInterface compatible controllers.
     * @return WebSocketControllerStack
     */
    public function setControllers(array $controllers): WebSocketControllerStack {
        $this->controllerStack = [];
        return $this->addControllers($controllers);
    }

    /**
     * Process each controller in the stack and return the final Response.
     * 
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Basic response Frame to work with.
     *
     * @return Frame Response Frame.
     */
    public function execute(Server $server, Frame $frame): Frame {
        if (empty($this->controllerStack)) {
            // Return response Frame, if there are no more controllers left.
            return $frame;
        }

        // Process the controllers.
        $frame = array_shift($this->controllerStack)->execute($server, $frame, $this);
        return $frame;
    }
}
