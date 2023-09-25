<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\EventManager;

interface EventListenerInterface {
    public function run(Event &$event): void;
}