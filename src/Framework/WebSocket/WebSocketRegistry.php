<?php

/**
 * Copyright © WW Byte OÜ.
 */

namespace Framework\WebSocket;

use InvalidArgumentException;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Framework\Container\ClassContainer;
use Framework\WebSocket\WebSocketMessageHandler;
use Framework\WebSocket\WebSocketControllerInterface;

class WebSocketRegistry {
    private array $controllerStack = [];
    private string $messageHandler;

    public function __construct(private ClassContainer $classContainer) {}

    /**
     * Add WebSocketControllerInterface compatible controllers to the WebSocket controller stack.
     * 
     * @param array $controllers An array of WebSocketControllerInterface compatible controllers.
     * @return WebSocketRegistry
     */
    public function addControllers(array $controllers): WebSocketRegistry {
        foreach ($controllers as $controller) {
            if (!class_exists($controller) || !in_array(WebSocketControllerInterface::class, class_implements($controller))) {
                throw new InvalidArgumentException($controller . ' must implement ' . WebSocketControllerInterface::class . '!');
            }

            $this->controllerStack[] = $controller;
        }

        return $this;
    }

    /**
     * Remove a controller from WebSocket controller stack.
     * 
     * @param array $controllerClassNames An array of controller class names to to remove.
     * @return WebSocketRegistry
     */
    public function removeControllers(array $controllerClassNames): WebSocketRegistry {
        foreach ($controllerClassNames as $id => $controller) {
            unset($this->controllerStack[$id]);
        }

        return $this;
    }

    /**
     * Replace the WebSocket controller stack controllers.
     * 
     * @param array $controllers An array of WebSocketControllerInterface compatible controllers.
     * @return WebSocketRegistry
     */
    public function setControllers(array $controllers): WebSocketRegistry {
        $this->controllerStack = [];
        return $this->addControllers($controllers);
    }

    /**
     * Set WebSocket message handler.
     * 
     * @param string $handler A WebSocketMessageHandlerInterface compatible handler.
     * @return WebSocketRegistry
     */
    public function setHandler(string $handler): WebSocketRegistry {
        if (!class_exists($handler) || !in_array(WebSocketMessageHandlerInterface::class, class_implements($handler))) {
            throw new InvalidArgumentException($handler . ' must implement ' . WebSocketMessageHandlerInterface::class . '!');
        }

        $this->messageHandler = $handler;
        return $this;
    }

    /**
     * Returns the WebSocket message handler instance. If a specific message handler
     * class is set, it will be used; otherwise, it falls back to the default WebSocketMessageHandler class.
     *
     * @return WebSocketMessageHandlerInterface The WebSocket message handler instance.
     */
    public function getMessageHandler(): WebSocketMessageHandlerInterface {
        return $this->classContainer->get($this->messageHandler ?? WebSocketMessageHandler::class, [$this->getControllerStack()], singleton: false);
    }

    /**
     * Returns the WebSocket controller stack, which is an array of controller class names.
     *
     * @return array An array of WebSocketControllerInterface compatible classes.
     */
    public function getControllerStack(): array {
        return $this->controllerStack;
    }

    /**
     * Process each controller in the stack and return the final Frame.
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

        // Process the middleware
        $controller = array_shift($this->controllerStack);
        $controller = $this->classContainer->get($controller, singleton: false);
        return $controller->execute($server, $frame, $this);
    }
}
