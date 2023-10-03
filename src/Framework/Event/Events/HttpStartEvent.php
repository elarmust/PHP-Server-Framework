<?php

/**
 * @copyright  WereWolf Labs OÜ
 */

namespace Framework\Event\Events;

use Framework\Framework;

class HttpStartEvent {
    private Framework $framework;

    public function __construct(Framework $framework) {
        $this->framework = $framework;
    }

    public function getFramework(): Framework {
        return $this->framework;
    }
}
