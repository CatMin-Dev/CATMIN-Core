-- Generic backup for module tables (MySQL/MariaDB).
-- It creates archive_<table> copies for every table matching @module_table_like.

SET @module_table_like := 'mod\_%';

SELECT GROUP_CONCAT(
		CONCAT(
				'CREATE TABLE IF NOT EXISTS `archive_',
				REPLACE(table_name, '`', '``'),
				'` AS SELECT * FROM `',
				REPLACE(table_name, '`', '``'),
				'`'
		)
		SEPARATOR '; '
) INTO @backup_sql
FROM information_schema.tables
WHERE table_schema = DATABASE()
	AND table_name LIKE @module_table_like;

SET @backup_sql := IFNULL(@backup_sql, 'SELECT ''No module tables matched'' AS info');
PREPARE backup_stmt FROM @backup_sql;
EXECUTE backup_stmt;
DEALLOCATE PREPARE backup_stmt;
