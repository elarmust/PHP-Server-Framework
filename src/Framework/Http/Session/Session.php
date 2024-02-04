<?php

/**
 * @copyright Elar Must.
 */

namespace Framework\Http\Session;

use Framework\Database\Database;
use Framework\Logger\Logger;
use Framework\Cache\Cache;
use Framework\Framework;
use RuntimeException;
use Throwable;

class Session {
    private array $data = [];
    private ?string $id = null;
    private ?int $timeStamp = null;
    private int $expiration;
    private bool $httpOnly = false;
    private bool $secure = false;
    private string $sessionPath = '/';
    public const STORAGE_MEMORY = 2;
    public const STORAGE_COLD = 1;

    public function __construct (private Framework $framework, private Logger $logger, private Database $database, int $expiration = 86400) {
        $this->expiration = max(0, $expiration);
    }

    /**
     * Returns a session.
     * If the session ID is not provided, a new session will be created.
     * If the session ID does not exist, a new session will be created.
     *
     * @param string $sessionId The session ID.
     * @return Session The loaded session.
     */
    public function getSession(string|null $sessionId = null): Session {
        if ($sessionId === null) {
            return $this->create();
        }

        $inCache = Cache::getTable(self::getTableName())->get($sessionId);
        $data = $inCache ? $inCache : ($this->getDatabase()->select(self::getTableName(), where: ['id' => $sessionId])[0] ?? false);

        // Return a new session if the session does not exist.
        if (!$data) {
            return $this->create();
        }

        $session = $this->clone($sessionId, unserialize($data['data']), $data['timestamp']);
        $timeStamp = $session->getTimestamp();

        // If the session has expired, then return a new session.
        if (($timeStamp - $session->getTimestamp()) > $session->getExpirationSeconds()) {
            $session->delete();
            return $this->create();
        }

        $session->setTimeStamp($timeStamp);
        if ($timeStamp != $data['timestamp']) {
            if ($inCache !== false) {
                $this->setCached($sessionId, $session->getData(), $timeStamp);
            }

            go(function () use ($sessionId, $timeStamp) {
                $this->getDatabase()->update(self::getTableName(), ['timestamp' => $timeStamp], where: ['id' => $sessionId]);
            });
        }

        return $session;
    }

    /**
     * Creates a new session with the given data and stores it in the database.
     *
     * @param array $data The data to be stored in the session.
     * @return Session The newly created session.
     * @throws RuntimeException If the session creation in the database fails.
     */
    public function create(array $data = []): Session {
        $timeStamp = time();
        $sessionId = $this->generateSessionId();
        $session = $this->clone($sessionId, $data, $timeStamp);

        // Save, if data is not empty.
        if ($session->getData() !== []) {
            $insertedId = $session->getDatabase()->insert(self::getTableName(), ['id' => $sessionId, 'data' => serialize($data), 'timestamp' => $timeStamp]);
            if ($insertedId === false) {
                throw new RuntimeException('Failed to save a session to database!');
            }

            $this->setCached($sessionId, $data, $timeStamp);
        }

        return $session;
    }

    /**
     * Saves the session data to the database.
     *
     * @return Session The updated session object.
     * @throws RuntimeException If the session is not instantiated or fails to save to the database.
     */
    public function save(): Session {
        if ($this->id() === null) {
            throw new RuntimeException('Cannot save non-instanciated session.');
        }

        // There is no need to save an empty session.
        if ($this->getData() === []) {
            $this->delete();
            return $this;
        }

        $serializedData = serialize($this->data);
        $timeStamp = time();
        go(function () use ($serializedData, $timeStamp) {
            $this->getDatabase()->query('
                INSERT INTO
                    ' . self::getTableName() . '
                SET
                    id = ?,
                    data = ?,
                    timestamp = ?
                ON DUPLICATE KEY UPDATE
                    data = ?,
                    timestamp = ?
            ', [
                $this->id(),
                $serializedData,
                $timeStamp,
                $serializedData,
                $timeStamp
            ]);
        });

        $this->setCached($this->id(), ['data' => $serializedData, 'timestamp' => $timeStamp], $this->getTimestamp());
        return $this;
    }

    /**
     * Sets the data for the session.
     *
     * @param array $data The data to be set for the session.
     *
     * @throws RuntimeException If the session is not instantiated.
     * @return Session Returns the updated session object.
     */
    public function setData(array $data): Session {
        if ($this->id() === null) {
            throw new RuntimeException('Cannot set non-instantiated session.');
        }

        $this->setTimeStamp(time());
        $this->data = array_merge($this->data, $data);

        // We might as well delete it, if data is empty.
        if ($this->getData() === []) {
            $this->delete();
            return $this;
        }

        $this->setCached($this->id(), $this->data, $this->getTimestamp());

        return $this;
    }

    /**
     * Deletes the session from the database and removes it from the session cache.
     *
     * @throws RuntimeException If the session fails to be deleted from the database.
     * @return Session The updated session object.
     */
    public function delete(): Session {
        $status = $this->getDatabase()->delete(self::getTableName(), ['id' => $this->id()]);
        if (!$status) {
            throw new RuntimeException('Failed to delete session from database!');
        }

        Cache::getTable(self::getTableName())->del($this->id());

        return $this;
    }

    /**
     * Checks if a record with the given ID exists in the cache/database.
     *
     * @param string $id Session id.
     *
     * @return bool True if the record exists, false otherwise.
     */
    public function exists(string $sessionId): bool {
        return (bool) $this->sessionStorageLocation($sessionId);
    }

    /**
     * Returns a random 32 character session id.
     *
     * @return string
     */
    public function generateSessionId(): string {
        while (true) {
            $randomString = bin2hex(random_bytes(16));

            if (!$this->sessionStorageLocation($randomString)) {
                return $randomString;
            }
        }
    }

    /**
     * Sets the cached session data for a given session ID.
     *
     * @param string $sessionId Session ID.
     * @param array $data Session data to be cached.
     * @param int $timeStamp Timestamp for the session data.
     * @return void
     */
    private function setCached(string $sessionId, array $data, int $timeStamp): void {
        try {
            $existingData = Cache::getTable(self::getTableName())->get($sessionId)['data'] ?? serialize([]);
            Cache::getTable(self::getTableName())->set($sessionId, ['data' => serialize(array_replace_recursive(unserialize($existingData), $data)), 'timestamp' => $timeStamp]);
        } catch (Throwable $e) {
            $this->logger->debug('Unable to save session to cache!', identifier: 'framework');
            $this->logger->debug($e, identifier: 'framework');
        }
    }

    /**
     * Clones the session object with the provided id, data, and timestamp.
     *
     * @param string $id Session id.
     * @param mixed $data Session data.
     * @param int $timeStamp Session timestamp.
     *
     * @return Session Cloned session object.
     */
    private function clone(string $id, $data, $timeStamp): Session {
        $session = clone $this;
        $session->id = $id;
        $session->data = $data;
        $session->timeStamp = $timeStamp;
        return $session;
    }

    /**
     * Retrieves data from the session.
     *
     * @param array $keys An optional array of keys to retrieve. If not provided, all data will be returned.
     *
     * @throws RuntimeException If any of the provided keys are invalid.
     * @return array The retrieved data.
     */
    public function getData(array $keys = []): array {
        if (!$keys) {
            return $this->data;
        }

        $invalidKeys = array_diff($keys, array_keys($this->data));
        if ($invalidKeys) {
            throw new RuntimeException('Invalid data keys: ' . implode(', ', $invalidKeys));
        }

        return array_intersect_key($this->data, array_flip($keys));
    }

    /**
     * Get an array of all available model data keys.
     *
     * @return array An array of model data keys.
     */
    public function getDataKeys(): array {
        return array_keys($this->data);
    }

    /**
     * Returns Session::STORAGE_MEMORY, if session is stored in memory,
     * Session::STORAGE_COLD if session is stored in cold storage,
     * false if session does not exist.
     *
     * @param string $sessionId Session ID.
     *
     * @return int
     */
    public function sessionStorageLocation(string $sessionId): int|bool {
        if (Cache::getTable(self::getTableName())->exists($sessionId)) {
            return $this::STORAGE_MEMORY;
        }

        if ($this->database->select(self::getTableName(), ['id'], ['id' => $sessionId])) {
            return true;
        }

        return false;
    }

    /**
     * Get the session ID.
     *
     * @return null|string Session ID or null if not set.
     */
    public function id(): null|string {
        return $this->id;
    }

    /**
     * Returns the expiration time of the session in seconds.
     *
     * @return int The expiration time in seconds.
     */
    public function getExpirationSeconds(): int {
        return $this->expiration;
    }

    /**
     * Sets the expiration time for the session in seconds.
     *
     * @param int $seconds The number of seconds until the session expires.
     *
     * @return void
     */
    public function setExpirationSeconds(int $seconds): void {
        $this->expiration = $seconds;
    }

    /**
     * Returns the name of the table used for storing session data
     * for cold and cache storage.
     *
     * @return string The table name.
     */
    public static function getTableName(): string {
        return 'sessions';
    }

    /**
     * Retrieves the database object associated with the session.
     *
     * @return Database Database used for session cold storage.
     */
    public function getDatabase(): Database {
        return $this->database;
    }

    /**
     * Get the timestamp of the session.
     *
     * @return null|int Session timestamp.
     */
    public function getTimestamp(): null|int {
        return $this->timeStamp;
    }

    /**
     * Set the timestamp for the session.
     *
     * @param int $timeStamp The timestamp to set.
     *
     * @throws RuntimeException If the timestamp is invalid.
     * @return Session
     */
    public function setTimeStamp(int $timeStamp): Session {
        // Validate timestamp against the UNIX timestamp.
        if ($timeStamp < 0) {
            throw new RuntimeException('Invalid timestamp: ' . $timeStamp);
        }

        $this->timeStamp = $timeStamp;
        return $this;
    }

    /**
     * Sets the flag indicating whether the session cookie should be accessible only through the HTTP protocol.
     *
     * @param bool $httpOnly Whether the session cookie should be accessible only through the HTTP protocol.
     * @return void
     */
    public function setHttpOnly(bool $httpOnly): void {
        $this->httpOnly = $httpOnly;
    }

    /**
     * Returns whether the session cookie is HTTP only.
     *
     * @return bool True if the session cookie is HTTP only, false otherwise.
     */
    public function getHttpOnly(): bool {
        return $this->httpOnly;
    }

    /**
     * Sets the secure flag for the session.
     *
     * @param bool $secure Whether the session should be secure or not.
     *
     * @return void
     */
    public function setSecure(bool $secure): void {
        $this->secure = $secure;
    }

    /**
     * Get the value of the secure flag for the session.
     *
     * @return bool Value of secure flag.
     */
    public function getSecure(): bool {
        return $this->secure;
    }

    /**
     * Sets the session path.
     *
     * @param string $path Path to set for the session.
     *
     * @return void
     */
    public function setSessionPath(string $path): void {
        $this->sessionPath = $path;
    }

    /**
     * Returns the session path.
     *
     * @return string Session path.
     */
    public function getSessionPath(): string {
        return $this->sessionPath;
    }

    /**
     * Get the value of the data key.
     *
     * @param string $name The name of the data key.
     *
     * @throws RuntimeException If the property does not exist.
     * @return mixed The value of the property.
     */
    public function __get($name) {
        return $this->getData([$name]);
    }

    /**
     * Sets the value of a model data key.
     *
     * @param string $name The name of the data key.
     * @param mixed $value The value to be set.
     *
     * @return void
     */
    public function __set($name, $value): void {
        $this->setData([$name => $value]);
    }

    /**
     * Checks if a data key is set.
     *
     * @param string $name The name of the data key to check.
     *
     * @return bool Returns true if the data key is set, false otherwise.
     */
    public function __isset($name): bool {
        return array_key_exists($name, $this->data);
    }

    /**
     * Unsets a model's data key.
     *
     * @param string $name The name of the data key to unset.
     *
     * @return void
     */
    public function __unset($name): void {
        unset($this->data[$name]);
    }

    /**
     * Retursn mode's data array.
     *
     * @return array The model data as an array.
     */
    public function __toArray(): array {
        return $this->getData();
    }

    /**
     * Returns a JSON representation of the model data.
     *
     * @return string The JSON representation of the model data.
     */
    public function __toString(): string {
        return json_encode($this->data);
    }
}
