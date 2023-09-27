<?php

/**
 * This interface defines the contract that WebSocket message handlers must adhere to.
 *
 * @package Framework\WebSocket
 * @copyright © WereWolf Labs OÜ.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use Framework\WebSocket\WebSocketControllerStack;
use OpenSwoole\WebSocket\Server;

interface WebSocketMessageHandlerInterface {
    /**
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Frame returned by previous controller in the stack.
     * @param WebSocketControllerStack $controllerStack WebSocket controller stack instance.
     *
     * @return Frame Response Frame.
     */
    public function handle(Server $server, Frame $frame, WebSocketControllerStack $controllerStack): Frame;
}
