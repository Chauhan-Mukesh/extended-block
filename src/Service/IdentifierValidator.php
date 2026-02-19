<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - SQL Identifier Validator.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Service;

use InvalidArgumentException;

/**
 * Utility class for validating and sanitizing SQL identifiers.
 *
 * This class provides methods to ensure that identifiers (table names, column names, etc.)
 * are safe to use in SQL queries. It helps prevent SQL injection attacks by validating
 * that identifiers match expected patterns.
 *
 * Security measures:
 * - Validates identifiers against a strict alphanumeric + underscore pattern
 * - Ensures identifiers start with a letter or underscore
 * - Enforces maximum length limits
 * - Provides sanitization for edge cases
 */
class IdentifierValidator
{
    /**
     * Maximum allowed length for a SQL identifier.
     * MySQL default is 64, we use a slightly lower value for safety.
     */
    public const MAX_IDENTIFIER_LENGTH = 64;

    /**
     * Pattern for valid SQL identifiers.
     * Allows letters, numbers, and underscores, starting with letter or underscore.
     */
    private const VALID_IDENTIFIER_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    /**
     * Validates a SQL identifier (table name, column name, etc.).
     *
     * @param string $identifier The identifier to validate
     *
     * @return bool True if the identifier is valid
     */
    public static function isValidIdentifier(string $identifier): bool
    {
        if (empty($identifier)) {
            return false;
        }

        if (strlen($identifier) > self::MAX_IDENTIFIER_LENGTH) {
            return false;
        }

        return (bool) preg_match(self::VALID_IDENTIFIER_PATTERN, $identifier);
    }

    /**
     * Validates and returns a SQL identifier, throwing exception if invalid.
     *
     * @param string $identifier  The identifier to validate
     * @param string $description Description for error message (e.g., "table name", "column name")
     *
     * @throws InvalidArgumentException If the identifier is invalid
     *
     * @return string The validated identifier
     */
    public static function validateIdentifier(string $identifier, string $description = 'identifier'): string
    {
        if (empty($identifier)) {
            throw new InvalidArgumentException(sprintf('Invalid %s: cannot be empty', $description));
        }

        if (strlen($identifier) > self::MAX_IDENTIFIER_LENGTH) {
            throw new InvalidArgumentException(sprintf('Invalid %s "%s": exceeds maximum length of %d characters', $description, $identifier, self::MAX_IDENTIFIER_LENGTH));
        }

        if (!preg_match(self::VALID_IDENTIFIER_PATTERN, $identifier)) {
            throw new InvalidArgumentException(sprintf('Invalid %s "%s": must contain only letters, numbers, and underscores, and start with a letter or underscore', $description, $identifier));
        }

        return $identifier;
    }

    /**
     * Validates a field name from a Pimcore field definition.
     *
     * @param string $fieldName The field name to validate
     *
     * @throws InvalidArgumentException If the field name is invalid
     *
     * @return string The validated field name
     */
    public static function validateFieldName(string $fieldName): string
    {
        return self::validateIdentifier($fieldName, 'field name');
    }

    /**
     * Validates a class ID.
     *
     * @param string $classId The class ID to validate
     *
     * @throws InvalidArgumentException If the class ID is invalid
     *
     * @return string The validated class ID
     */
    public static function validateClassId(string $classId): string
    {
        return self::validateIdentifier($classId, 'class ID');
    }

    /**
     * Validates a table name.
     *
     * @param string $tableName The table name to validate
     *
     * @throws InvalidArgumentException If the table name is invalid
     *
     * @return string The validated table name
     */
    public static function validateTableName(string $tableName): string
    {
        return self::validateIdentifier($tableName, 'table name');
    }

    /**
     * Validates a column name.
     *
     * @param string $columnName The column name to validate
     *
     * @throws InvalidArgumentException If the column name is invalid
     *
     * @return string The validated column name
     */
    public static function validateColumnName(string $columnName): string
    {
        return self::validateIdentifier($columnName, 'column name');
    }

    /**
     * Validates a block type name.
     *
     * @param string $typeName The block type name to validate
     *
     * @throws InvalidArgumentException If the type name is invalid
     *
     * @return string The validated block type name
     */
    public static function validateBlockTypeName(string $typeName): string
    {
        return self::validateIdentifier($typeName, 'block type name');
    }

    /**
     * Validates a table prefix.
     *
     * @param string $prefix The table prefix to validate
     *
     * @throws InvalidArgumentException If the prefix is invalid
     *
     * @return string The validated prefix
     */
    public static function validateTablePrefix(string $prefix): string
    {
        return self::validateIdentifier($prefix, 'table prefix');
    }
}
