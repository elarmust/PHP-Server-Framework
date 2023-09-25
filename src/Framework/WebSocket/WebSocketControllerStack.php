<?php

/**
 * Manages a stack of WebSocket controllers, enabling easy addition, removal, and execution of WebSocket controllers.
 * 
 * Copyright © WereWolf Labs OÜ.
 */

namespace Framework\WebSocket;

use InvalidArgumentException;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Framework\Http\WebSocketControllerInterface;
use Framework\WebSocket\WebSocketControllerStackInterface;

class WebSocketControllerStack implements WebSocketControllerStackInterface {
    private array $controllerStack = [];

    /**
     * Initialize a controller stack with an array of WebSocketControllerInterface compatible controllers.
     * 
     * @param array $controllerStack An array of WebSocketControllerInterface compatible controllers.
     */
    public function __construct(array $controllerStack) {
        $this->controllerStack = $controllerStack;
    }

    /**
     * Add WebSocketControllerStackInterface compatible controllers to the WebSocket controller stack.
     * 
     * @param array $controllers An array of WebSocketControllerStackInterface compatible controllers.
     * @return WebSocketControllerStackInterface
     */
    public function addControllers(array $controllers): WebSocketControllerStackInterface {
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
     * @return WebSocketControllerStackInterface
     */
    public function removeControllers(array $controllerClassNames): WebSocketControllerStackInterface {
        foreach ($controllerClassNames as $controller) {
            unset($this->controllerStack[$controller]);
        }

        return $this;
    }

    /**
     * Replace the WebSocket controller stack controllers.
     * 
     * @param array $controllers An array of WebSocketControllerStackInterface compatible controllers.
     * @return WebSocketControllerStackInterface
     */
    public function setControllers(array $controllers): WebSocketControllerStackInterface {
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
