-- Generic purge for module tables (MySQL/MariaDB).
-- It drops every table matching @module_table_like.

SET @module_table_like := 'mod\_%';

SELECT GROUP_CONCAT(
		CONCAT(
				'DROP TABLE IF EXISTS `',
				REPLACE(table_name, '`', '``'),
				'`'
		)
		SEPARATOR '; '
) INTO @purge_sql
FROM information_schema.tables
WHERE table_schema = DATABASE()
	AND table_name LIKE @module_table_like;

SET @purge_sql := IFNULL(@purge_sql, 'SELECT ''No module tables matched'' AS info');
PREPARE purge_stmt FROM @purge_sql;
EXECUTE purge_stmt;
DEALLOCATE PREPARE purge_stmt;
