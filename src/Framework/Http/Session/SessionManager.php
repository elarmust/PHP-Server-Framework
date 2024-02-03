<?php

/**
 * This class facilitates session management.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session;

use Framework\Model\Exception\ModelException;
use Generator;

use Framework\Http\Session\Session;
use Framework\Database\Database;
use Framework\Vault\Vault;
use Framework\Vault\Table;
use Framework\Framework;
use RuntimeException;

class SessionManager {
    private Database $database;
    private Table $sessions;
    private SessionModel $sessionModel;
    private int $expiration;
    public const STORAGE_MEMORY = 2;
    public const STORAGE_COLD = 1;

    public function __construct(private Framework $framework) {
        $this->expiration = $this->framework->getConfiguration()->getConfig('sessionExpirationSeconds') ?: 86400;
        $this->sessions = Vault::getTable('session');
        $this->database = $this->getSessionDatabase();
        $this->sessionModel = $framework->getClassContainer()->get(SessionModel::class, [$this->database]);
    }

    /**
     * Returns a random 32 character session id.
     *
     * @return string
     */
    public function generateSessionId(): string {
        while (true) {
            $randomString = bin2hex(random_bytes(16));

            if (!$this->whereIsSessionStored($randomString)) {
                return $randomString;
            }
        }
    }

    /**
     * Return session object.
     *
     * @param int $sessionId Session ID.
     * @return Session
     */
    public function getSession(?string $sessionId = null): Session {
        if (!$sessionId) {
            $session = new Session($this, $this->sessionModel, $this->generateSessionId(), [], time());
            $session->save();
            return $session;
        }

        $storageLocation = $this->whereIsSessionStored($sessionId);
        if ($storageLocation !== false) {
            $session = $this->loadSessionFromStorage($sessionId, $storageLocation);

            // If session is expired, generate a new one.
            if ((time() - $session->getTimestamp()) > $this->expiration) {
                $this->deleteSession($sessionId);
                $newId = $this->generateSessionId();

                $session = new Session($this, $this->sessionModel, $newId, [], time());
                $session->save();
                return $session;
            }

            // Update session timestamp
            $session->updateTimestamp();
            return $session;
        }

        $newId = $this->generateSessionId();
        $session = new Session($this, $this->sessionModel, $newId, [], time());
        $session->save();
        return $session;
    }

    /**
     * Returns SessionManager::STORAGE_MEMORY, if session is stored in memory,
     * SessionManager::STORAGE_COLD if session is stored in cold storage,
     * false if session does not exist.
     *
     * @param string $sessionId Session ID.
     *
     * @return int
     */
    public function whereIsSessionStored(string $sessionId): int|bool {
        if ($this->sessions->exists($sessionId)) {
            return $this::STORAGE_MEMORY;
        }

        if ($this->sessionModel->exists($sessionId)) {
            return $this::STORAGE_COLD;
        }

        return false;
    }

    public function loadSessionFromStorage(string $sessionId, int $storageLocation): Session {
        if ($storageLocation === false) {
            throw new RuntimeException('Session ' . $sessionId . ' does not exist.');
        }

        if ($storageLocation === $this::STORAGE_MEMORY) {
            $sessionData = $this->sessions->get($sessionId);
        } else {
            $sessionData = $this->sessionModel->load($sessionId)->getData();
        }

        return new Session($this, $this->sessionModel, $sessionId, unserialize($sessionData['data']), $sessionData['timestamp']);
    }

    /**
     * Retrieves session ids from memory storage.
     *
     * @return Generator
     */
    public function getSessionIds(): Generator {
        foreach ($this->sessions as $sessionId) {
            yield $sessionId;
        }
    }

    public function deleteSession(string $sessionId): void {
        $this->sessions->del($sessionId);

        try {
            $this->sessionModel->delete($sessionId);
        } catch (ModelException) {
            // Does not exist in cold storage.
        }
    }

    public function getExpirationSeconds(): int {
        return $this->expiration;
    }

    public function setExpirationSeconds(int $seconds): void {
        $this->expiration = $seconds;
    }

    public function getSessionTable(): Table {
        return $this->sessions;
    }
}
