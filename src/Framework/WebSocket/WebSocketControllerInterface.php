<?php

/**
 * This interface defines the fundamental function of a WebSocket controller.
 * 
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use Framework\WebSocket\WebSocketMessageHandlerInterface;
use OpenSwoole\WebSocket\Server;

interface WebSocketControllerInterface {
    /**
     * Process the WebSocket controller and return a Frame response.
     * 
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Frame returned by previous controller in the stack.
     * @param WebSocketMessageHandlerInterface $messageHandler WebSocket message handler instance.
     *
     * @return Frame
     */
    public function execute(Server $server, Frame $frame, WebSocketMessageHandlerInterface $messageHandler): Frame;
}
