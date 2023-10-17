<?php

/**
 * This class serves as an implementation of the Psr\EventDispatcher\ListenerProviderInterface,
 * allowing you to register, retrieve, and unregister event listeners for various events.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Event;

use Framework\Event\EventListenerInterface;
use Framework\Framework;
use InvalidArgumentException;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventListenerProvider implements ListenerProviderInterface {
    private $listeners = [];

    public function __construct(private Framework $framework) {
    }

    /**
     * Add a listener to an event.
     *
     * @param string $eventClass Event class name.
     * @param EventListenerInterface $listener An instance of a listener.
     * @param array $precedingDependency = [] an array of listener class names that need to come before the current listener.
     * @param array $followingDependency = [] an array of listener class names that need to come after the current listener
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function registerEventListener(string $eventClass, EventListenerInterface $listener, array $precedingDependency = [], array $followingDependency = []): void {
        if (!class_exists($eventClass)) {
            throw new InvalidArgumentException($eventClass . ' must be an object!');
        }

        $this->listeners[$eventClass][$listener::class] = [$listener, $precedingDependency, $followingDependency];
        $this->order($eventClass);
    }

    /**
     * Returns event listeners for a given event.
     *
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable {
        foreach ($this->listeners[$event::class] ?? [] as $listenerData) {
            yield $listenerData[0];
        }
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

        unset($this->listeners[$eventClassName][$eventListener]);

        if (count($this->listeners[$eventClassName]) == 0) {
            unset($this->listeners[$eventClassName]);
        } else {
            $this->order($eventClassName);
        }
    }

    private function order(string $eventName): void {
        if (!$this->listeners[$eventName] ?? null) {
            return;
        }

        $graph = [];
        foreach ($this->listeners[$eventName] as $key => $data) {
            $graph[$key] = [$data[1], $data[2]];
        }

        $graph = $this->framework->getModuleRegistry()->topologicalSort($graph);
        $eventListeners = [];

        foreach ($graph as $listener) {
            $eventListeners[$listener] = $this->listeners[$eventName][$listener];
        }

        $this->listeners[$eventName] = $eventListeners;
    }
}
