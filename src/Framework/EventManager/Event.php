<?php

/**
 * a Class to hold event data
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\EventManager;

class Event {
    private array $eventData;
    private bool $isCanceled = false;

    public function __construct(mixed $eventData) {
        $this->eventData = $eventData;
    }

    public function cancel(): void {
        $this->isCanceled = true;
    }

    public function resume(): void {
        $this->isCanceled = false;
    }

    public function isCanceled(): bool {
        return $this->isCanceled;
    }

    public function getData(): array {
        return $this->eventData;
    }

    public function setData(array $data): void {
        foreach ($data as $key => $value) {
            $this->eventData[$key] = $value;
        }
    }
}
