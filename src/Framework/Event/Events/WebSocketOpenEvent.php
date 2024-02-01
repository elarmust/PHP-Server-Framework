<?php

/**
 * @copyright  Elar Must
 */

namespace Framework\Event\Events;

use Framework\Framework;
use OpenSwoole\Http\Request;
use Psr\EventDispatcher\StoppableEventInterface;

class WebSocketOpenEvent implements StoppableEventInterface {
    private bool $stopped = false;

    public function __construct(private Framework $framework, private Request $request) {
    }

    public function getServer(): Framework {
        return $this->framework;
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
