<?php

/**
 * This class represents a user session and provides methods to access and manage session data.
 *
 * @copyright WereWolf Labs OÜ.
 */

namespace Framework\Http\Session;

class Session {
    private int $timestamp;

    /**
     * @param string $sessionId
     * @param array $data = []
     */
    public function __construct(private string $sessionId, private array $sessionData = []) {
        $this->timestamp = time();
    }

    /**
     * Get Session ID.
     * 
     * @return string
     */
    public function getId(): string {
        return $this->sessionId;
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
