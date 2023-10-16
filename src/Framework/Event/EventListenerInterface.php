<?php

/**
 * This interface defines the contract that event listeners must adhere to.
 * Event listeners are responsible for handling events when they are dispatched.
 * 
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Event;

interface EventListenerInterface {
    /**
     * Handle an event.
     *
     * @param object $event The event to handle.
     * @return void
     */
    public function __invoke(object $event): void;
}
