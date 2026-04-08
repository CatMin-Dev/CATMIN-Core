<?php

declare(strict_types=1);

use Core\database\ConnectionManager;

final class CoreBackupExporter
{
    /**
     * @return array{ok:bool,path:string,name:string,size:int,error:string}
     */
    public function exportInitialInstall(string $driver): array
    {
        $driver = strtolower(trim($driver));
        $stamp = date('Ymd-His');
        $dir = CATMIN_STORAGE . '/backups/install';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['ok' => false, 'path' => '', 'name' => '', 'size' => 0, 'error' => 'Dossier backup non writable.'];
        }

        try {
            if ($driver === 'sqlite') {
                $source = (string) config('database.connections.sqlite.database', CATMIN_ROOT . '/db/database.sqlite');
                if (!is_file($source)) {
                    return ['ok' => false, 'path' => '', 'name' => '', 'size' => 0, 'error' => 'Fichier SQLite introuvable.'];
                }
                $name = 'catmin-initial-db-backup-' . $stamp . '.sqlite';
                $path = $dir . '/' . $name;
                if (!@copy($source, $path)) {
                    return ['ok' => false, 'path' => '', 'name' => '', 'size' => 0, 'error' => 'Copie SQLite impossible.'];
                }
                $size = (int) (@filesize($path) ?: 0);
                return ['ok' => true, 'path' => $path, 'name' => $name, 'size' => $size, 'error' => ''];
            }

            $pdo = (new ConnectionManager())->connection($driver !== '' ? $driver : null);
            $sql = $this->buildSqlDump($pdo);
            $name = 'catmin-initial-db-backup-' . $stamp . '.sql';
            $path = $dir . '/' . $name;
            $ok = @file_put_contents($path, $sql, LOCK_EX);
            if ($ok === false) {
                return ['ok' => false, 'path' => '', 'name' => '', 'size' => 0, 'error' => 'Ecriture dump SQL impossible.'];
            }

            $size = (int) (@filesize($path) ?: 0);
            return ['ok' => true, 'path' => $path, 'name' => $name, 'size' => $size, 'error' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'path' => '', 'name' => '', 'size' => 0, 'error' => $e->getMessage()];
        }
    }

    private function buildSqlDump(\PDO $pdo): string
    {
        $driver = (string) $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $lines = [
            '-- CATMIN initial install SQL backup',
            '-- Generated: ' . date('c'),
            '-- Driver: ' . $driver,
            '',
        ];

        $quoteIdent = static function (string $identifier) use ($driver): string {
            if ($driver === 'pgsql') {
                return '"' . str_replace('"', '""', $identifier) . '"';
            }
            return '`' . str_replace('`', '``', $identifier) . '`';
        };

        $exportRows = static function (string $tableName) use ($pdo, $quoteIdent, &$lines): void {
            $tableIdent = $quoteIdent($tableName);
            $rowsStmt = $pdo->query('SELECT * FROM ' . $tableIdent);
            $rows = $rowsStmt !== false ? ($rowsStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            if (!is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                $columns = [];
                $values = [];
                foreach ($row as $col => $value) {
                    $columns[] = $quoteIdent((string) $col);
                    if ($value === null) {
                        $values[] = 'NULL';
                    } elseif (is_bool($value)) {
                        $values[] = $value ? '1' : '0';
                    } elseif (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/', $value) === 1)) {
                        $values[] = (string) $value;
                    } else {
                        $values[] = $pdo->quote((string) $value);
                    }
                }

                $lines[] = 'INSERT INTO ' . $tableIdent . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
            }

            $lines[] = '';
        };

        if ($driver === 'sqlite') {
            $tablesStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC");
            $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($tables as $table) {
                $name = (string) ($table['name'] ?? '');
                $sql = trim((string) ($table['sql'] ?? ''));
                if ($name === '' || $sql === '') {
                    continue;
                }
                $lines[] = $sql . ';';
                $lines[] = '';
                $exportRows($name);
            }

            return implode(PHP_EOL, $lines) . PHP_EOL;
        }

        $tablesStmt = $pdo->query('SHOW TABLES');
        $tables = $tablesStmt !== false ? ($tablesStmt->fetchAll(\PDO::FETCH_COLUMN) ?: []) : [];
        foreach ($tables as $tableNameRaw) {
            $tableName = (string) $tableNameRaw;
            if ($tableName === '') {
                continue;
            }

            $createStmt = $pdo->query('SHOW CREATE TABLE ' . $quoteIdent($tableName));
            $createRow = $createStmt !== false ? $createStmt->fetch(\PDO::FETCH_ASSOC) : false;
            if (is_array($createRow)) {
                $createSql = '';
                foreach ($createRow as $k => $v) {
                    if (is_string($k) && stripos($k, 'create table') !== false) {
                        $createSql = (string) $v;
                        break;
                    }
                }
                if ($createSql !== '') {
                    $lines[] = $createSql . ';';
                    $lines[] = '';
                }
            }

            $exportRows($tableName);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
