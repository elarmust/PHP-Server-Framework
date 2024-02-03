<?php

/**
 * This class represents a user session and provides methods to access and manage session data.
 *
 * @copyright Elar Must.
 */

namespace Framework\Http\Session;

use Framework\Model\Model;
use Framework\Database\Database;
use RuntimeException;
use Framework\Framework;

class Session extends Model {
    protected array $properties = [
        'data',
        'timestamp'
    ];

    public function __construct (Framework $framework) {
        $dbName = $this->framework->getConfiguration()->getConfig('session.sessionColdStorage.mysqlDb') ?: 'default';
        $databaseInfo = $this->framework->getConfiguration()->getConfig('databases.' . $dbName);

        if (!$databaseInfo) {
            throw new RuntimeException('Database ' . $dbName . ' does not exist.');
        }

        $database = $this->framework->getClassContainer()->get(Database::class, [
            $databaseInfo['host'],
            $databaseInfo['port'],
            $databaseInfo['database'],
            $databaseInfo['username'],
            $databaseInfo['password']
        ], $dbName);

        parent::__construct(...$framework->getClassContainer()->prepareFunctionArguments(parent::class, parameters: [$database]));
    }

    public function load(string|int $modelId, bool $includeArchived = false): Session {
        // Load from memory storage.
        $session = parent::load($modelId);

        // TODO: if tiemstamp is expired, then return a new session.

        $session->timestamp = time();
        return $session;
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
