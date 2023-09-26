<?php

/**
 * Event system
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\EventManager;

use Psr\Log\LogLevel;
use ReflectionException;
use Framework\Logger\Logger;
use InvalidArgumentException;
use Framework\Core\ClassContainer;

class EventManager {
    private array $eventListeners = [];
    private ClassContainer $classContainer;
    private Logger $logger;

    public function __construct(ClassContainer $classContainer, Logger $logger) {
        $this->classContainer = $classContainer;
        $this->logger = $logger;
    }

    /**
     * Dispatch event along with event data
     *
     * @param string $eventName
     * @param mixed $data
     * @return Event
     * @throws ReflectionException
     */
    public function dispatchEvent(string $eventName, array $data = []): Event {
        $event = new Event($data);
        foreach ($this->eventListeners[$eventName] ?? [] as $eventClass) {
            $eventListener = $this->classContainer->get($eventClass);
            $eventListener->run($event);
        }

        return $event;
    }

    public function getEventList(): array {
        return array_keys($this->eventListeners);
    }

    public function getEventListeners(string $eventName): array {
        return $this->eventListeners[$eventName] ?? [];
    }

    public function registerEventListener(string $eventName, string $eventListener): void {
        if (!is_subclass_of($eventListener, EventListenerInterface::class)) {
            throw new InvalidArgumentException('Event listener must be an instance of ' . EventListenerInterface::class . '!');
        }

        $this->eventListeners[$eventName][] = $eventListener;
    }

    public function unregisterEventListener(string $eventName, string $eventListener): void {
        if (!is_subclass_of($eventListener, EventListenerInterface::class)) {
            throw new InvalidArgumentException('Event listener must be an instance of ' . EventListenerInterface::class . '!');
        }

        $key = array_search($eventListener, $this->eventListeners[$eventName]);
        if ($key === false) {
            $this->logger->log(LogLevel::NOTICE, 'Attempting to unregister event handler: \'' . $eventListener . '\' for event \'' . $eventName . '\'');
            return;
        }

        unset($this->eventListeners[$eventName][$key]);

        if (count($this->eventListeners[$eventName]) == 0) {
            unset($this->eventListeners[$eventName]);
        }
    }
}
