<?php

/**
 * This interface defines the contract for WebSocket controllers, providing fundamental functionality.
 * 
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http;

use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

interface WebSocketControllerInterface {
    /**
     * Process the WebSocket controller and return a Frame response.
     * 
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Frame returned by previous controller in the stack.
     * @param WebSocketControllerInterface $controllerStack WebSocket controller stack instance.
     *
     * @return Frame
     */
    public function execute(Server $server, Frame $frame, WebSocketControllerInterface $controllerStack): Frame;
}
