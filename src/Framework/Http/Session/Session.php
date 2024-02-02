<?php

/**
 * This class represents a user session and provides methods to access and manage session data.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session;

use OpenSwoole\Coroutine;

class Session {
    /**
     * @param string $sessionId
     * @param array $data = []
     */
    public function __construct(
        private SessionManager $sessionManager,
        private SessionModel $sessionModel,
        private string $sessionId,
        private array $sessionData,
        private int $timestamp
    ) {
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

    public function save(): void {
        $serializedData = serialize($this->getData());
        $this->sessionManager->getSessionTable()->set($this->getId(), [
            'data' => $serializedData,
            'timestamp' => $this->getTimestamp()
        ]);

        $id = $this->getId();
        go (function() use ($serializedData, $id) {
            $this->sessionModel->load($id)->setData([
                'data' => $serializedData,
                'timestamp' => $this->getTimestamp()
            ])->save();
        });
    }
}
