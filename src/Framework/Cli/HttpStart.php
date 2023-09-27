<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\CLI;

use Framework\Cli\Cli;
use Framework\EventManager\Event;
use Framework\EventManager\EventListenerInterface;
use OpenSwoole\Event as SwooleEvent;

class HttpStart implements EventListenerInterface {
    private Cli $cli;

    public function __construct(Cli $cli) {
        $this->cli = $cli;
    }

    public function run(Event &$event): void {
        $this->cli->stdin = fopen('php://stdin', 'r');
        stream_set_blocking($this->cli->stdin, 0);
        SwooleEvent::add($this->cli->stdin, function () {
            $line = trim(fgets($this->cli->stdin));

            if ($line !== '') {
                $this->cli->runCommand(explode(' ', $line));
                readline_add_history($line);
                readline_write_history();
            }
        });
    }
}
