<?php

/**
 * This interface defines the contract that WebSocket message handlers must adhere to.
 *
 * @package Framework\WebSocket
 * @copyright © Elar Must.
 */

namespace Framework\WebSocket;

use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

interface WebSocketMessageHandlerInterface {
    /**
     * @param Server $server Current WebSocket server instance.
     * @param Frame $frame Frame returned by previous controller in the stack.
     *
     * @return Frame Response Frame.
     */
    public function handle(Server $server, Frame $frame): Frame;
}
