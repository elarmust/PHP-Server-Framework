<?php

/**
 * @copyright  WereWolf Labs OÜ
 */

namespace Framework\Event\Events;

use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Server;
use Psr\EventDispatcher\StoppableEventInterface;

class WebSocketOpenEvent implements StoppableEventInterface {
    private Server $server;
    private Request $request;
    private bool $stopped = false;
    public function __construct(Server $server, Request $request) {
        $this->server = $server;
        $this->request = $request;
    }

    public function getServer(): Server {
        return $this->server;
    }

    public function getRequest(): Request {
        return $this->request;
    }

    public function stopEvent(): void {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
