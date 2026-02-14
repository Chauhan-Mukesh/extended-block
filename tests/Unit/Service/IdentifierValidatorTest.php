<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Identifier Validator Unit Test.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Service;

use ExtendedBlockBundle\Service\IdentifierValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for IdentifierValidator class.
 *
 * Tests the validator's ability to:
 * - Validate SQL identifiers (table names, column names, etc.)
 * - Prevent SQL injection through invalid identifiers
 * - Enforce length and character constraints
 *
 * @covers \ExtendedBlockBundle\Service\IdentifierValidator
 */
class IdentifierValidatorTest extends TestCase
{
    /**
     * Data provider for valid identifiers.
     *
     * @return array<array<string>>
     */
    public static function validIdentifierProvider(): array
    {
        return [
            ['table_name'],
            ['TableName'],
            ['_underscore_start'],
            ['table123'],
            ['T1'],
            ['a'],
            ['_'],
            ['MyClass'],
            ['object_eb_MyClass_field'],
            ['field_name_123'],
            ['UPPERCASE'],
            ['mixedCase123'],
        ];
    }

    /**
     * Data provider for invalid identifiers.
     *
     * @return array<array<string>>
     */
    public static function invalidIdentifierProvider(): array
    {
        return [
            [''],                          // empty
            ['123table'],                  // starts with number
            ['table-name'],                // hyphen
            ['table.name'],                // dot
            ['table name'],                // space
            ['table;name'],                // semicolon
            ["table'name"],                // single quote
            ['table"name'],                // double quote
            ['table`name'],                // backtick
            ['table/*name'],               // comment start
            ['table*/name'],               // comment end
            ['table--name'],               // SQL comment
            ['table(name)'],               // parentheses
            ['table=name'],                // equals
            ['table@name'],                // at sign
            ['table#name'],                // hash
            ['table$name'],                // dollar sign
            ['table%name'],                // percent
            ['table^name'],                // caret
            ['table&name'],                // ampersand
            ['table*name'],                // asterisk
            ['table+name'],                // plus
            ['table\name'],                // backslash
            ['table/name'],                // forward slash
            ['table<name'],                // less than
            ['table>name'],                // greater than
            ['table!name'],                // exclamation
            ['table?name'],                // question mark
            ["'; DROP TABLE users; --"],   // SQL injection
            ['1; SELECT * FROM users'],    // SQL injection
        ];
    }

    /**
     * Tests valid identifiers are accepted.
     *
     * @dataProvider validIdentifierProvider
     */
    public function testValidIdentifiers(string $identifier): void
    {
        $this->assertTrue(IdentifierValidator::isValidIdentifier($identifier));
    }

    /**
     * Tests invalid identifiers are rejected.
     *
     * @dataProvider invalidIdentifierProvider
     */
    public function testInvalidIdentifiers(string $identifier): void
    {
        $this->assertFalse(IdentifierValidator::isValidIdentifier($identifier));
    }

    /**
     * Tests that validateIdentifier returns the identifier when valid.
     */
    public function testValidateIdentifierReturnsIdentifier(): void
    {
        $identifier = 'valid_identifier';
        $result = IdentifierValidator::validateIdentifier($identifier);
        $this->assertSame($identifier, $result);
    }

    /**
     * Tests that validateIdentifier throws exception for empty string.
     */
    public function testValidateIdentifierThrowsOnEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        IdentifierValidator::validateIdentifier('');
    }

    /**
     * Tests that validateIdentifier throws exception for too long identifier.
     */
    public function testValidateIdentifierThrowsOnTooLong(): void
    {
        $longIdentifier = str_repeat('a', 65);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('exceeds maximum length');
        IdentifierValidator::validateIdentifier($longIdentifier);
    }

    /**
     * Tests that validateIdentifier throws exception for invalid characters.
     */
    public function testValidateIdentifierThrowsOnInvalidChars(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain only letters, numbers, and underscores');
        IdentifierValidator::validateIdentifier('invalid-name');
    }

    /**
     * Tests SQL injection prevention - quotes in identifier.
     */
    public function testPreventsQuotesInIdentifier(): void
    {
        $this->assertFalse(IdentifierValidator::isValidIdentifier("table'; DROP TABLE users; --"));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table`; DROP TABLE users; --'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('"table"'));
    }

    /**
     * Tests SQL injection prevention - special characters.
     */
    public function testPreventsSpecialChars(): void
    {
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table;'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table--'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table/*'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table*/'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table\\'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('table\x00'));
    }

    /**
     * Tests SQL injection prevention - numeric start.
     */
    public function testPreventsNumericStart(): void
    {
        $this->assertFalse(IdentifierValidator::isValidIdentifier('1table'));
        $this->assertFalse(IdentifierValidator::isValidIdentifier('123'));
    }

    /**
     * Tests validateFieldName convenience method.
     */
    public function testValidateFieldName(): void
    {
        $result = IdentifierValidator::validateFieldName('field_name');
        $this->assertSame('field_name', $result);
    }

    /**
     * Tests validateClassId convenience method.
     */
    public function testValidateClassId(): void
    {
        $result = IdentifierValidator::validateClassId('MyClass');
        $this->assertSame('MyClass', $result);
    }

    /**
     * Tests validateTableName convenience method.
     */
    public function testValidateTableName(): void
    {
        $result = IdentifierValidator::validateTableName('object_eb_MyClass_field');
        $this->assertSame('object_eb_MyClass_field', $result);
    }

    /**
     * Tests validateColumnName convenience method.
     */
    public function testValidateColumnName(): void
    {
        $result = IdentifierValidator::validateColumnName('column_name');
        $this->assertSame('column_name', $result);
    }

    /**
     * Tests validateBlockTypeName convenience method.
     */
    public function testValidateBlockTypeName(): void
    {
        $result = IdentifierValidator::validateBlockTypeName('text_block');
        $this->assertSame('text_block', $result);
    }

    /**
     * Tests validateTablePrefix convenience method.
     */
    public function testValidateTablePrefix(): void
    {
        $result = IdentifierValidator::validateTablePrefix('object_eb_');
        $this->assertSame('object_eb_', $result);
    }

    /**
     * Tests that field name validation throws with proper description.
     */
    public function testFieldNameValidationThrowsWithDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid field name');
        IdentifierValidator::validateFieldName('invalid-field');
    }

    /**
     * Tests that class ID validation throws with proper description.
     */
    public function testClassIdValidationThrowsWithDescription(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid class ID');
        IdentifierValidator::validateClassId('invalid class');
    }

    /**
     * Tests maximum identifier length boundary.
     */
    public function testMaxLengthBoundary(): void
    {
        // Exactly 64 characters should be valid
        $validLength = str_repeat('a', 64);
        $this->assertTrue(IdentifierValidator::isValidIdentifier($validLength));

        // 65 characters should be invalid
        $invalidLength = str_repeat('a', 65);
        $this->assertFalse(IdentifierValidator::isValidIdentifier($invalidLength));
    }
}
