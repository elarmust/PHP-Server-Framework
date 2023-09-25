<?php

/**
 * Defines an interface for managing a stack of WebSocket controllers.
 * 
 * WebSocket controllers can be organized in a stack to handle incoming data.
 * 
 * Copyright © WereWolf Labs OÜ.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

interface WebSocketControllerStackInterface {
    /**
     * Process the controller stack and return a response.
     * 
     * @param Server $server
     * @param Frame $response
     *
     * @return Frame Response Frame.
     */
    public function execute(Server $server, Frame $response): Frame;
}
