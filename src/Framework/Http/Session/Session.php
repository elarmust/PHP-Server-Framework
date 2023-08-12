<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Http\Session;

class Session {
    private array $sessionData;
    private string $id;
    private int $timestamp;

    /**
     * @param string $sessionId
     * @param array $data
     */
    public function __construct(string $sessionId, array $data = []) {
        $this->id = $sessionId;
        $this->sessionData = $data;
        $this->timestamp = time();
    }

    /**
     * Get Session ID.
     * 
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * Get Session Data.
     * 
     * @return array
     */
    public function getData(): array {
        return $this->sessionData;
    }

    /**
     * Get Session timestamp.
     * 
     * @return int
     */
    public function getTimestamp(): int {
        return $this->timestamp;
    }

    /**
     * Update session timestamp.
     * 
     * @return void
     */
    public function updateTimestamp(): void {
        $this->timestamp = time();
    }

    /**
     * Set Session data.
     * 
     * @param array $data
     * @return void
     */
    public function setData(array $data): void {
        $this->sessionData = $data;
    }
}
