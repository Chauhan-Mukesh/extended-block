<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Table Schema Service.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Service;

use Doctrine\DBAL\Connection;
use Exception;
use ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data\ExtendedBlock;
use InvalidArgumentException;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;

/**
 * Service for managing Extended Block database tables.
 *
 * This service handles all database schema operations for extended blocks:
 * - Creating tables when fields are added to class definitions
 * - Updating table schemas when field definitions change
 * - Dropping tables when fields are removed (with user confirmation)
 * - Managing foreign key relationships
 *
 * Table naming convention:
 * - Main table: object_eb_{classId}_{fieldName}
 * - Localized table: object_eb_{classId}_{fieldName}_localized
 *
 * @see ExtendedBlock
 */
class TableSchemaService
{
    /**
     * Database connection.
     */
    protected Connection $db;

    /**
     * Table prefix from configuration.
     */
    protected string $tablePrefix;

    /**
     * Creates a new TableSchemaService.
     *
     * @param string $tablePrefix The table prefix (from configuration)
     *
     * @throws InvalidArgumentException If table prefix is invalid
     */
    public function __construct(string $tablePrefix = 'object_eb_')
    {
        $this->db = Db::get();
        $this->tablePrefix = IdentifierValidator::validateTablePrefix($tablePrefix);
    }

    /**
     * Creates or updates the table schema for an extended block field.
     *
     * This method is called when a class definition is saved and ensures
     * the database tables match the field definition.
     *
     * @param ClassDefinition $class           The class definition
     * @param ExtendedBlock   $fieldDefinition The extended block field
     */
    public function createOrUpdateTable(ClassDefinition $class, ExtendedBlock $fieldDefinition): void
    {
        $tableName = $this->getTableName($class->getId(), $fieldDefinition->getName());

        if ($this->tableExists($tableName)) {
            $this->updateTable($class, $fieldDefinition, $tableName);
        } else {
            $this->createTable($class, $fieldDefinition, $tableName);
        }

        // Handle localized table if needed
        if ($fieldDefinition->isAllowLocalizedFields() && $fieldDefinition->hasLocalizedFields()) {
            $localizedTableName = $tableName . '_localized';

            if ($this->tableExists($localizedTableName)) {
                $this->updateLocalizedTable($class, $fieldDefinition, $localizedTableName);
            } else {
                $this->createLocalizedTable($class, $fieldDefinition, $localizedTableName);
            }
        }
    }

    /**
     * Drops all tables associated with an extended block field.
     *
     * WARNING: This permanently deletes all data in the tables.
     *
     * @param string $classId   The class ID
     * @param string $fieldName The field name
     *
     * @throws InvalidArgumentException If classId or fieldName is invalid
     */
    public function dropTables(string $classId, string $fieldName): void
    {
        // getTableName() validates classId and fieldName
        $tableName = $this->getTableName($classId, $fieldName);
        $localizedTableName = $tableName . '_localized';

        // Validate the localized table name (may exceed length with _localized suffix)
        IdentifierValidator::validateTableName($localizedTableName);

        $quotedLocalizedTable = $this->db->quoteIdentifier($localizedTableName);
        $quotedTable = $this->db->quoteIdentifier($tableName);

        // Drop localized table first (foreign key dependency)
        if ($this->tableExists($localizedTableName)) {
            $this->db->executeStatement("DROP TABLE {$quotedLocalizedTable}");
            Logger::info("ExtendedBlock: Dropped table {$localizedTableName}");
        }

        // Drop main table
        if ($this->tableExists($tableName)) {
            $this->db->executeStatement("DROP TABLE {$quotedTable}");
            Logger::info("ExtendedBlock: Dropped table {$tableName}");
        }
    }

    /**
     * Generates the table name for an extended block field.
     *
     * @param string $classId   The class ID
     * @param string $fieldName The field name
     *
     * @throws InvalidArgumentException If classId or fieldName is invalid
     *
     * @return string The validated table name
     */
    public function getTableName(string $classId, string $fieldName): string
    {
        // Validate inputs before constructing the table name
        IdentifierValidator::validateClassId($classId);
        IdentifierValidator::validateFieldName($fieldName);

        $tableName = $this->tablePrefix . $classId . '_' . $fieldName;

        // Validate the full table name as well
        IdentifierValidator::validateTableName($tableName);

        return $tableName;
    }

    /**
     * Lists all extended block tables in the database.
     *
     * @return array<string> List of table names
     */
    public function listAllTables(): array
    {
        $tables = $this->db->fetchFirstColumn(
            'SELECT table_name FROM information_schema.tables 
             WHERE table_schema = DATABASE() AND table_name LIKE ?',
            [$this->tablePrefix . '%']
        );

        return $tables;
    }

    /**
     * Gets table statistics (row counts, sizes).
     *
     * @param string $tableName The table name
     *
     * @return array<string, mixed> Table statistics
     */
    public function getTableStats(string $tableName): array
    {
        if (!$this->tableExists($tableName)) {
            return ['exists' => false];
        }

        $stats = $this->db->fetchAssociative(
            'SELECT 
                TABLE_ROWS as row_count,
                DATA_LENGTH as data_size,
                INDEX_LENGTH as index_size,
                (DATA_LENGTH + INDEX_LENGTH) as total_size
             FROM information_schema.tables 
             WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        return [
            'exists' => true,
            'row_count' => (int) ($stats['row_count'] ?? 0),
            'data_size' => (int) ($stats['data_size'] ?? 0),
            'index_size' => (int) ($stats['index_size'] ?? 0),
            'total_size' => (int) ($stats['total_size'] ?? 0),
        ];
    }

    /**
     * Creates a new main table for an extended block field.
     *
     * @param ClassDefinition $class           The class definition
     * @param ExtendedBlock   $fieldDefinition The field definition
     * @param string          $tableName       The table name (already validated by getTableName())
     */
    protected function createTable(ClassDefinition $class, ExtendedBlock $fieldDefinition, string $tableName): void
    {
        $columns = $this->buildMainTableColumns($fieldDefinition);

        // Use quoteIdentifier to safely escape the table name
        $quotedTable = $this->db->quoteIdentifier($tableName);
        $sql = "CREATE TABLE {$quotedTable} (\n" . implode(",\n", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            $this->db->executeStatement($sql);
            Logger::info("ExtendedBlock: Created table {$tableName}");
        } catch (Exception $e) {
            Logger::error("ExtendedBlock: Failed to create table {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates an existing table to match the field definition.
     *
     * @param ClassDefinition $class           The class definition
     * @param ExtendedBlock   $fieldDefinition The field definition
     * @param string          $tableName       The table name (already validated by getTableName())
     */
    protected function updateTable(ClassDefinition $class, ExtendedBlock $fieldDefinition, string $tableName): void
    {
        $existingColumns = $this->getExistingColumns($tableName);
        $requiredColumns = $this->getRequiredColumns($fieldDefinition);

        $quotedTable = $this->db->quoteIdentifier($tableName);

        // Add missing columns
        foreach ($requiredColumns as $columnName => $columnDefinition) {
            if (!isset($existingColumns[$columnName])) {
                // Validate and quote the column name
                IdentifierValidator::validateColumnName($columnName);
                $quotedColumn = $this->db->quoteIdentifier($columnName);
                $sql = "ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$columnDefinition}";
                try {
                    $this->db->executeStatement($sql);
                    Logger::info("ExtendedBlock: Added column {$columnName} to {$tableName}");
                } catch (Exception $e) {
                    Logger::error("ExtendedBlock: Failed to add column {$columnName}: " . $e->getMessage());
                }
            }
        }

        // Note: We don't remove columns automatically to prevent data loss
        // Unused columns can be removed manually via the admin interface
    }

    /**
     * Creates the localized data table.
     *
     * @param ClassDefinition $class              The class definition
     * @param ExtendedBlock   $fieldDefinition    The field definition
     * @param string          $localizedTableName The localized table name (already validated by caller)
     */
    protected function createLocalizedTable(
        ClassDefinition $class,
        ExtendedBlock $fieldDefinition,
        string $localizedTableName,
    ): void {
        $columns = $this->buildLocalizedTableColumns($fieldDefinition);

        $quotedTable = $this->db->quoteIdentifier($localizedTableName);
        $sql = "CREATE TABLE {$quotedTable} (\n" . implode(",\n", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        try {
            $this->db->executeStatement($sql);
            Logger::info("ExtendedBlock: Created localized table {$localizedTableName}");
        } catch (Exception $e) {
            Logger::error('ExtendedBlock: Failed to create localized table: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates the localized data table.
     *
     * @param ClassDefinition $class              The class definition
     * @param ExtendedBlock   $fieldDefinition    The field definition
     * @param string          $localizedTableName The localized table name (already validated by caller)
     */
    protected function updateLocalizedTable(
        ClassDefinition $class,
        ExtendedBlock $fieldDefinition,
        string $localizedTableName,
    ): void {
        $existingColumns = $this->getExistingColumns($localizedTableName);
        $requiredColumns = $this->getLocalizedRequiredColumns($fieldDefinition);

        $quotedTable = $this->db->quoteIdentifier($localizedTableName);

        foreach ($requiredColumns as $columnName => $columnDefinition) {
            if (!isset($existingColumns[$columnName])) {
                // Validate and quote the column name
                IdentifierValidator::validateColumnName($columnName);
                $quotedColumn = $this->db->quoteIdentifier($columnName);
                $sql = "ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$columnDefinition}";
                try {
                    $this->db->executeStatement($sql);
                    Logger::info("ExtendedBlock: Added column {$columnName} to {$localizedTableName}");
                } catch (Exception $e) {
                    Logger::error('ExtendedBlock: Failed to add column: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Checks if a table exists.
     *
     * @param string $tableName The table name
     *
     * @return bool True if table exists
     */
    protected function tableExists(string $tableName): bool
    {
        $result = $this->db->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        return (bool) $result;
    }

    /**
     * Gets existing columns from a table.
     *
     * @param string $tableName The table name
     *
     * @return array<string, string> Column names and types
     */
    protected function getExistingColumns(string $tableName): array
    {
        $columns = [];

        $rows = $this->db->fetchAllAssociative(
            'SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        foreach ($rows as $row) {
            $columns[$row['COLUMN_NAME']] = $row['COLUMN_TYPE'];
        }

        return $columns;
    }

    /**
     * Builds column definitions for the main table.
     *
     * @param ExtendedBlock $fieldDefinition The field definition
     *
     * @throws InvalidArgumentException If any field name is invalid
     *
     * @return array<string> Column definitions for CREATE TABLE
     */
    protected function buildMainTableColumns(ExtendedBlock $fieldDefinition): array
    {
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`o_id` INT(11) UNSIGNED NOT NULL COMMENT "Reference to parent object"',
            '`fieldname` VARCHAR(70) NOT NULL COMMENT "Field name in the class"',
            '`index` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT "Position in block"',
            '`type` VARCHAR(100) NOT NULL DEFAULT "default" COMMENT "Block type identifier"',
        ];

        // Add columns for each field in children definitions
        foreach ($fieldDefinition->getFieldDefinitions() as $field) {
            if ($field instanceof Localizedfields) {
                continue;
            }

            $fieldName = $field->getName();
            $fieldTitle = $field->getTitle() ?: $fieldName;

            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            // These fields use getQueryColumnType() which returns multiple columns (e.g., id and type)
            if ($field instanceof QueryResourcePersistenceAwareInterface && method_exists($field, 'getQueryColumnType')) {
                $queryColumnTypes = $field->getQueryColumnType();
                if (is_array($queryColumnTypes)) {
                    foreach ($queryColumnTypes as $colSuffix => $colType) {
                        // Column names: fieldname__id, fieldname__type
                        $colName = $fieldName . '__' . $colSuffix;
                        IdentifierValidator::validateColumnName($colName);
                        $quotedCol = $this->db->quoteIdentifier($colName);
                        $columns[] = sprintf(
                            '%s %s COMMENT "%s (%s)"',
                            $quotedCol,
                            $colType,
                            addslashes($fieldTitle),
                            $colSuffix
                        );
                    }
                    continue;
                }
            }

            // Handle simple fields with getColumnType
            if (method_exists($field, 'getColumnType')) {
                $columnType = $field->getColumnType();
                if ($columnType) {
                    IdentifierValidator::validateColumnName($fieldName);
                    $quotedField = $this->db->quoteIdentifier($fieldName);
                    $columns[] = sprintf(
                        '%s %s COMMENT "%s"',
                        $quotedField,
                        $columnType,
                        addslashes($fieldTitle)
                    );
                }
            }
        }

        // Add indexes
        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `idx_object` (`o_id`)';
        $columns[] = 'INDEX `idx_fieldname` (`fieldname`)';
        $columns[] = 'INDEX `idx_type` (`type`)';
        $columns[] = 'INDEX `idx_object_field` (`o_id`, `fieldname`)';

        return $columns;
    }

    /**
     * Builds column definitions for the localized table.
     *
     * @param ExtendedBlock $fieldDefinition The field definition
     *
     * @throws InvalidArgumentException If any field name is invalid
     *
     * @return array<string> Column definitions for CREATE TABLE
     */
    protected function buildLocalizedTableColumns(ExtendedBlock $fieldDefinition): array
    {
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`ooo_id` INT(11) UNSIGNED NOT NULL COMMENT "Reference to main block item"',
            '`language` VARCHAR(10) NOT NULL COMMENT "Language code (e.g., en, de)"',
        ];

        // LocalizedFields are no longer supported in ExtendedBlock.
        // This table structure is maintained for backward compatibility with
        // existing installations that may have localized data.

        // Add indexes
        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `idx_item` (`ooo_id`)';
        $columns[] = 'INDEX `idx_language` (`language`)';
        $columns[] = 'UNIQUE KEY `uk_item_language` (`ooo_id`, `language`)';

        return $columns;
    }

    /**
     * Gets required columns for the main table.
     *
     * @param ExtendedBlock $fieldDefinition The field definition
     *
     * @throws InvalidArgumentException If any field name is invalid
     *
     * @return array<string, string> Column names and types
     */
    protected function getRequiredColumns(ExtendedBlock $fieldDefinition): array
    {
        $columns = [
            'id' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'o_id' => 'INT(11) UNSIGNED NOT NULL',
            'fieldname' => 'VARCHAR(70) NOT NULL',
            'index' => 'INT(11) UNSIGNED NOT NULL DEFAULT 0',
            'type' => 'VARCHAR(100) NOT NULL DEFAULT "default"',
        ];

        foreach ($fieldDefinition->getFieldDefinitions() as $field) {
            if ($field instanceof Localizedfields) {
                continue;
            }

            $fieldName = $field->getName();

            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            if ($field instanceof QueryResourcePersistenceAwareInterface && method_exists($field, 'getQueryColumnType')) {
                $queryColumnTypes = $field->getQueryColumnType();
                if (is_array($queryColumnTypes)) {
                    foreach ($queryColumnTypes as $colSuffix => $colType) {
                        $colName = $fieldName . '__' . $colSuffix;
                        IdentifierValidator::validateColumnName($colName);
                        $columns[$colName] = $colType;
                    }
                    continue;
                }
            }

            // Handle simple fields with getColumnType
            if (method_exists($field, 'getColumnType')) {
                $columnType = $field->getColumnType();
                if ($columnType) {
                    IdentifierValidator::validateColumnName($fieldName);
                    $columns[$fieldName] = $columnType;
                }
            }
        }

        return $columns;
    }

    /**
     * Gets required columns for the localized table.
     *
     * @param ExtendedBlock $fieldDefinition The field definition
     *
     * @throws InvalidArgumentException If any field name is invalid
     *
     * @return array<string, string> Column names and types
     */
    protected function getLocalizedRequiredColumns(ExtendedBlock $fieldDefinition): array
    {
        $columns = [
            'id' => 'INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            'ooo_id' => 'INT(11) UNSIGNED NOT NULL',
            'language' => 'VARCHAR(10) NOT NULL',
        ];

        // Note: LocalizedFields not allowed in ExtendedBlock
        // This method returns base columns only

        return $columns;
    }
}
