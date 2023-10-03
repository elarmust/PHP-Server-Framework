<?php

/**
 * @copyright  WereWolf Labs OÜ
 */

namespace Framework\Event\Events;

use OpenSwoole\WebSocket\Server;
use Psr\EventDispatcher\StoppableEventInterface;

class WebSocketCloseEvent implements StoppableEventInterface {
    private Server $server;
    private int $connectionId;
    private bool $stopped = false;

    public function __construct(Server $server, int $connectionId) {
        $this->server = $server;
        $this->connectionId = $connectionId;
    }

    public function getServer(): Server {
        return $this->server;
    }

    public function getConnectionId(): int {
        return $this->connectionId;
    }

    public function stopEvent(): void {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool {
        return $this->stopped;
    }
}
