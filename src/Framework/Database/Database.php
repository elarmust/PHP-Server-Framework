<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Database;

use PDO;
use Throwable;
use Psr\Log\LogLevel;
use Framework\Logger\Logger;
use OpenSwoole\Core\Coroutine\Pool\ClientPool;
use OpenSwoole\Core\Coroutine\Client\PDOConfig;
use OpenSwoole\Core\Coroutine\Client\PDOClientFactory;

class Database {
    private ClientPool $pool;

    public function __construct(
        private Logger $logger,
        private string $host,
        private int $port,
        private string $database,
        private string $username,
        private string $password,
        private string $charset = 'utf8mb4',
        private int $maxPoolSize = 25
    ) {
        $mysqlConfig = new PDOConfig();
        $mysqlConfig->withHost($this->host);
        $mysqlConfig->withPort($this->port);
        $mysqlConfig->withUsername($this->username);
        $mysqlConfig->withPassword($this->password);
        $mysqlConfig->withDbname($this->database);
        $mysqlConfig->withCharset($charset);
        $this->pool = new ClientPool(PDOClientFactory::class, $mysqlConfig, $maxPoolSize);
    }

    public function getHost() {
        return $this->host;
    }

    public function getPort() {
        return $this->port;
    }

    public function getName() {
        return $this->database;
    }

    public function getUsername() {
        return $this->username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function selectSql(string $table, ?array $data, array $where = null): array {
        if (!$data) {
            $data = ['*'];
        }

        foreach ($data as $field) {
            $fields[] = $field;
        }

        $values = [];
        $query = '
            SELECT 
                ' . implode(',', $fields) . '
            FROM
                ' . $table;
        if ($where) {
            $whereTemp = $this->whereSql($where);
            $values = $whereTemp['values'];
            $query .= '
            WHERE
                ' . $whereTemp['fields'];
        }

        return [
            $query,
            $values
        ];
    }

    public function select(string $table, ?array $data = null, array $where = null) {
        $select = $this->selectSql($table, $data, $where);
        return $this->query($select[0], $select[1]);
    }

    public function insertSql(string $table, array $data): array {
        $fields = [];
        $values = [];
        $sqlValues = [];

        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $value;
            $sqlValues[] = '?';
        }

        $fieldsString = '`';
        $fieldsString .= implode('` , `', $fields);
        $fieldsString .= '`';

        $query = '
        INSERT INTO
            ' . $table . ' (' . $fieldsString . ')
        VALUES (' . implode(', ', $sqlValues) . ')';

        return [
            $query,
            $values
        ];
    }

    /**
     * Insert a single entry into database.
     *
     * @param string $table Table name.
     * @param array $data Data to insert.
     *
     * @return bool|int Returns the id of the inserted entry.
     */
    public function insert(string $table, array $data): bool|int {
        $insert = $this->insertSql($table, $data);
        $pdo = $this->pool->get();
        $sql = $pdo->prepare($insert[0]);
        $return = false;
        try {
            $sql->execute($insert[1]);
            $return = $pdo->lastInsertId();
        } catch (Throwable $e) {
            $this->logger->log(LogLevel::ERROR, $e, identifier: 'framework');
        } finally {
            $this->pool->put($pdo);
        }

        return $return;
    }

    public function update(string $table, array $data, array $where = null): bool {
        $whereFields = '';
        $values = [];

        foreach ($data as $field => $value) {
            if (is_array($value)) {
                $fields[] = $field . ' = ' . $field . ' ' . $value[0] . ' ?';
                $values[] = $value[1];
            } else {
                $fields[] = $field . ' = ?';
                $values[] = $value;
            }
        }

        if (is_array($where)) {
            $whereTemp = $this->whereSql($where);
            $whereFields = $whereTemp['fields'];
            $values = array_merge($values, $whereTemp['values']);
        }

        $query = 'UPDATE ' . $table . ' SET ' . implode(',', $fields) . ' WHERE ' . $whereFields;

        return $this->query($query, $values);
    }

    public function delete(string $table, array $where): bool {
        $whereFields = [];
        $values = [];
        if (is_array($where)) {
            $whereTemp = $this->whereSql($where);
            $whereFields = $whereTemp['fields'];
            $values = $whereTemp['values'];
        }

        $query = 'DELETE FROM ' . $table . ' WHERE ' . $whereFields;
        return $this->query($query, $values);
    }

    /**
     * Prepare and execute SQL queries.
     *
     * @param array $query SQL query to process.
     * @param array $params List of parameters to prepare.
     *
     * @return bool|array Returns bool or an array of results.
     */
    public function query(string $query, array $params = null): bool|array {
        $return = false;
        $pdo = $this->pool->get();
        $sql = $pdo->prepare($query);
        try {
            $return = $sql->execute($params);
            if ($sql->columnCount()) {
                $return = $sql->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Throwable $e) {
            $this->logger->log(LogLevel::ERROR, $e, identifier: 'framework');
        } finally {
            $this->pool->put($pdo);
        }

        return $return;
    }

    private function whereSql(array $where): array {
        $fields = [];
        $values = [];
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                $fields[] = $field . ' IN (' . implode(',', array_fill(0, count($value), '?')) . ')';
                $values = array_merge($values, $value);
            } else {
                $fields[] = $field . ' = ?';
                $values[] = $value;
            }
        }

        return ['values' => $values, 'fields' => implode(' AND ', $fields)];
    }
}
