<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Event\Events;

use Framework\Framework;
use Psr\EventDispatcher\StoppableEventInterface;

class WebSocketCloseEvent implements StoppableEventInterface {
    private bool $stopped = false;

    public function __construct(private Framework $framework, private int $connectionId) {
    }

    public function getServer(): Framework {
        return $this->framework;
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
