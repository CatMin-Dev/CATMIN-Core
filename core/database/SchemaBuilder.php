<?php

declare(strict_types=1);

namespace Core\database;

use PDO;

final class SchemaBuilder
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $driver
    ) {}

    public function create(string $table, array $columns, array $indexes = []): void
    {
        $tableSql = $this->quote($table);
        $columnSql = implode(', ', array_map(fn (array $column): string => $this->compileColumn($column), $columns));
        $sql = sprintf('CREATE TABLE IF NOT EXISTS %s (%s)', $tableSql, $columnSql);

        $this->pdo->exec($sql);

        foreach ($indexes as $index) {
            $this->createIndex($table, $index);
        }
    }

    public function createIndex(string $table, array $index): void
    {
        $name = (string) ($index['name'] ?? 'idx_' . $table . '_' . implode('_', $index['columns'] ?? []));
        $columns = $index['columns'] ?? [];
        if (!is_array($columns) || $columns === []) {
            return;
        }

        $quotedColumns = implode(', ', array_map(fn (string $column): string => $this->quote($column), $columns));
        $unique = !empty($index['unique']) ? 'UNIQUE ' : '';

        $sql = sprintf(
            'CREATE %sINDEX IF NOT EXISTS %s ON %s (%s)',
            $unique,
            $this->quote($name),
            $this->quote($table),
            $quotedColumns
        );

        if ($this->driver === 'sqlsrv') {
            $sql = sprintf(
                'IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N\'%s\') CREATE %sINDEX %s ON %s (%s)',
                str_replace("'", "''", $name),
                $unique,
                $this->quote($name),
                $this->quote($table),
                $quotedColumns
            );
        }

        $this->pdo->exec($sql);
    }

    private function compileColumn(array $column): string
    {
        $name = $this->quote((string) ($column['name'] ?? ''));
        $type = (string) ($column['type'] ?? 'string');
        $length = (int) ($column['length'] ?? 255);
        $nullable = (bool) ($column['nullable'] ?? false);
        $default = $column['default'] ?? null;
        $primary = (bool) ($column['primary'] ?? false);
        $autoIncrement = (bool) ($column['auto_increment'] ?? false);
        $unsigned = (bool) ($column['unsigned'] ?? false);

        if ($primary && $autoIncrement) {
            return $this->compileAutoIncrementPrimary($name);
        }

        $sqlType = $this->mapType($type, $length, $unsigned);
        $parts = [$name, $sqlType];

        if (!$nullable) {
            $parts[] = 'NOT NULL';
        }

        if ($default !== null) {
            $parts[] = 'DEFAULT ' . $this->normalizeDefault($default);
        }

        if ($primary) {
            $parts[] = 'PRIMARY KEY';
        }

        return implode(' ', $parts);
    }

    private function compileAutoIncrementPrimary(string $name): string
    {
        return match ($this->driver) {
            'mysql' => $name . ' BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'pgsql' => $name . ' BIGSERIAL PRIMARY KEY',
            'sqlsrv' => $name . ' BIGINT IDENTITY(1,1) PRIMARY KEY',
            default => $name . ' INTEGER PRIMARY KEY AUTOINCREMENT',
        };
    }

    private function mapType(string $type, int $length, bool $unsigned): string
    {
        return match ($type) {
            'string' => 'VARCHAR(' . $length . ')',
            'text' => 'TEXT',
            'boolean' => $this->driver === 'sqlsrv' ? 'BIT' : 'BOOLEAN',
            'integer' => ($unsigned && $this->driver === 'mysql') ? 'INT UNSIGNED' : 'INT',
            'bigint' => ($unsigned && $this->driver === 'mysql') ? 'BIGINT UNSIGNED' : 'BIGINT',
            'datetime' => $this->driver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME',
            'json' => ($this->driver === 'pgsql') ? 'JSONB' : 'TEXT',
            default => strtoupper($type),
        };
    }

    private function normalizeDefault(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value) && strtoupper($value) === 'CURRENT_TIMESTAMP') {
            return 'CURRENT_TIMESTAMP';
        }

        return $this->pdo->quote((string) $value);
    }

    private function quote(string $identifier): string
    {
        return match ($this->driver) {
            'mysql', 'sqlite' => '`' . str_replace('`', '``', $identifier) . '`',
            'sqlsrv' => '[' . str_replace(']', ']]', $identifier) . ']',
            default => '"' . str_replace('"', '""', $identifier) . '"',
        };
    }
}
