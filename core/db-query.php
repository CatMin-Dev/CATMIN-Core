<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/db-connection.php';

final class CoreDbQuery
{
    private CoreDbConnection $connection;

    public function __construct(?CoreDbConnection $connection = null)
    {
        $this->connection = $connection ?? new CoreDbConnection();
    }

    public function run(string $sql, array $params = [], ?string $connectionName = null): array
    {
        $pdo = $this->connection->get($connectionName);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    }

    public function execute(string $sql, array $params = [], ?string $connectionName = null): bool
    {
        $pdo = $this->connection->get($connectionName);
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function transaction(callable $callback, ?string $connectionName = null): mixed
    {
        $pdo = $this->connection->get($connectionName);
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
