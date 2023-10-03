<?php

/**
 * This class serves as an implementation of the Psr\EventDispatcher\ListenerProviderInterface,
 * allowing you to register, retrieve, and unregister event listeners for various events.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Event;

use Framework\Event\EventListenerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventListenerProvider implements ListenerProviderInterface {
    private $listeners = [];

    /**
     * Add a listener to an event.
     *
     * @param string $eventClass Event class name.
     * @param EventListenerInterface $listener An instance of a listener.
     */
    public function registerEventListener(string $eventClass, EventListenerInterface $listener) {
        $this->listeners[$eventClass][] = $listener;
    }

    /**
     * Returns event listeners for a given event.
     * 
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable {
        return $this->listeners[$event::class] ?? [];
    }

    /**
     * Unregister an event listener for a specific event.
     *
     * @param string $eventClassName Event class name.
     * @param string $eventListener Class name of the listener to unregister from an event.
     */
    public function unregisterEventListener(string $eventClassName, string $eventListener): void {
        if (!isset($this->listeners[$eventClassName])) {
            return;
        }

        foreach ($this->listeners[$eventClassName] as $key => $registeredListener) {
            if ($registeredListener === $eventListener) {
                unset($this->listeners[$eventClassName][$key]);
            }
        }

        if (count($this->listeners[$eventClassName]) == 0) {
            unset($this->listeners[$eventClassName]);
        }
    }
}
