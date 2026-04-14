<?php

declare(strict_types=1);

use Core\database\SchemaBuilder;

return static function (SchemaBuilder $schema, array $prefixes): void {
    // No structural change required.
    // This migration exists to persist schema_version=0.1.0-RC.1 in core_db_versions.
};
