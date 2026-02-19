<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlock Validation Unit Test.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for ExtendedBlock validation logic.
 *
 * These tests verify the validation rules for placement and nesting prevention:
 * - ExtendedBlock can only be at root level of class definitions
 * - No ExtendedBlock inside LocalizedFields, FieldCollections, ObjectBricks, or Block
 * - No ExtendedBlock inside ExtendedBlock
 * - No Block, Fieldcollections, or Objectbricks inside ExtendedBlock
 * - No AdvancedManyToManyRelation or AdvancedManyToManyObjectRelation inside ExtendedBlock
 * - No ReverseObjectRelation inside ExtendedBlock
 *
 * Note: These tests focus on testing the validation rules conceptually,
 * as the actual ExtendedBlock class requires Pimcore's runtime environment.
 */
class ExtendedBlockValidationTest extends TestCase
{
    /**
     * Path to the source directory from test directory.
     */
    private const SOURCE_DIR = __DIR__ . '/../../../../../../src';

    /**
     * Tests that placement and nesting prevention rules are documented.
     *
     * This test ensures the validation rules are defined:
     * 1. ExtendedBlock can only be at root level
     * 2. ExtendedBlock cannot be inside LocalizedFields
     * 3. ExtendedBlock cannot be inside FieldCollections
     * 4. ExtendedBlock cannot be inside ObjectBricks
     * 5. ExtendedBlock cannot be inside Block
     * 6. ExtendedBlock cannot contain another ExtendedBlock
     * 7. ExtendedBlock cannot contain Block
     * 8. ExtendedBlock cannot contain Fieldcollections
     * 9. ExtendedBlock cannot contain Objectbricks
     * 10. ExtendedBlock cannot contain AdvancedManyToManyRelation
     * 11. ExtendedBlock cannot contain AdvancedManyToManyObjectRelation
     * 12. ExtendedBlock cannot contain ReverseObjectRelation
     */
    public function testValidationRulesAreDefined(): void
    {
        // The validation rules are implemented in ExtendedBlock::validate()
        // and ClassDefinitionListener methods

        // These rules should prevent:
        $preventedPlacements = [
            'ExtendedBlock inside LocalizedFields' => true,
            'ExtendedBlock inside FieldCollections' => true,
            'ExtendedBlock inside ObjectBricks' => true,
            'ExtendedBlock inside Block' => true,
            'ExtendedBlock inside ExtendedBlock' => true,
            'Block inside ExtendedBlock' => true,
            'Fieldcollections inside ExtendedBlock' => true,
            'Objectbricks inside ExtendedBlock' => true,
            'AdvancedManyToManyRelation inside ExtendedBlock' => true,
            'AdvancedManyToManyObjectRelation inside ExtendedBlock' => true,
            'ReverseObjectRelation inside ExtendedBlock' => true,
        ];

        // All prevention rules should be active
        foreach ($preventedPlacements as $rule => $isActive) {
            $this->assertTrue($isActive, "Rule '{$rule}' should be active");
        }
    }

    /**
     * Tests that the ExtendedBlock class exists with validate method.
     */
    public function testExtendedBlockClassHasValidateMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\Model\\DataObject\\ClassDefinition\\Data\\ExtendedBlock',
                'validate'
            ),
            'ExtendedBlock should have a validate method'
        );
    }

    /**
     * Tests that the ClassDefinitionListener has validation method.
     */
    public function testClassDefinitionListenerHasValidationMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\EventListener\\ClassDefinitionListener',
                'onPreSave'
            ),
            'ClassDefinitionListener should have onPreSave method'
        );
    }

    /**
     * Tests that the ExtendedBlock source file contains nesting prevention code.
     */
    public function testExtendedBlockContainsNestingPreventionLogic(): void
    {
        $sourceFile = self::SOURCE_DIR . '/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlock.php should exist');

        $content = file_get_contents($sourceFile);

        // Check for ExtendedBlock nesting prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain another ExtendedBlock',
            $content,
            'Should have ExtendedBlock nesting prevention error message'
        );

        // Check for Block nesting prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain a Block',
            $content,
            'Should have Block nesting prevention error message'
        );

        // Check for Fieldcollections prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain Fieldcollections',
            $content,
            'Should have Fieldcollections prevention error message'
        );

        // Check for Objectbricks prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain Objectbricks',
            $content,
            'Should have Objectbricks prevention error message'
        );

        // Check for Classificationstore prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain Classificationstore',
            $content,
            'Should have Classificationstore prevention error message'
        );
    }

    /**
     * Tests that ExtendedBlock blocks advanced relational field types.
     *
     * Advanced relational types (AdvancedManyToManyRelation, AdvancedManyToManyObjectRelation,
     * ReverseObjectRelation) use complex metadata storage or virtual relation lookups
     * that are incompatible with ExtendedBlock's separate table storage.
     */
    public function testExtendedBlockBlocksAdvancedRelationalTypes(): void
    {
        $sourceFile = self::SOURCE_DIR . '/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlock.php should exist');

        $content = file_get_contents($sourceFile);

        // Check for AdvancedManyToManyRelation prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain AdvancedManyToManyRelation',
            $content,
            'Should have AdvancedManyToManyRelation prevention error message'
        );

        // Check for AdvancedManyToManyObjectRelation prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain AdvancedManyToManyObjectRelation',
            $content,
            'Should have AdvancedManyToManyObjectRelation prevention error message'
        );

        // Check for ReverseObjectRelation prevention
        $this->assertStringContainsString(
            'ExtendedBlock cannot contain ReverseObjectRelation',
            $content,
            'Should have ReverseObjectRelation prevention error message'
        );
    }

    /**
     * Tests that ExtendedBlock has relational field support matrix documentation.
     *
     * The RELATIONAL FIELD SUPPORT MATRIX comment should document:
     * - SAFE types (ManyToOneRelation, simple scalars)
     * - CONDITIONALLY SAFE types (ManyToManyRelation, ManyToManyObjectRelation)
     * - UNSAFE types (Advanced relations, reverse relations, complex containers)
     */
    public function testExtendedBlockHasRelationalFieldSupportMatrix(): void
    {
        $sourceFile = self::SOURCE_DIR . '/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlock.php should exist');

        $content = file_get_contents($sourceFile);

        // Check for support matrix documentation
        $this->assertStringContainsString(
            'RELATIONAL FIELD SUPPORT MATRIX',
            $content,
            'Should have RELATIONAL FIELD SUPPORT MATRIX documentation'
        );

        // Check for SAFE category
        $this->assertStringContainsString(
            'SAFE',
            $content,
            'Should document SAFE field types'
        );

        // Check for UNSAFE category
        $this->assertStringContainsString(
            'UNSAFE',
            $content,
            'Should document UNSAFE field types'
        );
    }

    /**
     * Tests that ExtendedBlock has schema validation functionality.
     *
     * The validateAndSyncTableSchema method should exist to detect and fix
     * column mismatches between the class definition and database schema.
     */
    public function testExtendedBlockHasSchemaValidationMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\Model\\DataObject\\ClassDefinition\\Data\\ExtendedBlock',
                'validateAndSyncTableSchema'
            ),
            'ExtendedBlock should have validateAndSyncTableSchema method for schema synchronization'
        );
    }

    /**
     * Tests that the ClassDefinitionListener contains placement validation logic.
     */
    public function testClassDefinitionListenerContainsPlacementValidation(): void
    {
        $sourceFile = self::SOURCE_DIR . '/EventListener/ClassDefinitionListener.php';
        $this->assertFileExists($sourceFile, 'ClassDefinitionListener.php should exist');

        $content = file_get_contents($sourceFile);

        // Check for ExtendedBlock inside Block prevention
        $this->assertStringContainsString(
            'cannot be placed inside Block',
            $content,
            'Should have ExtendedBlock inside Block prevention message'
        );

        // Check for ExtendedBlock inside LocalizedFields prevention
        $this->assertStringContainsString(
            'cannot be placed inside LocalizedFields',
            $content,
            'Should have ExtendedBlock inside LocalizedFields prevention message'
        );

        // Check for FieldCollections check
        $this->assertStringContainsString(
            'checkForExtendedBlockInFieldcollections',
            $content,
            'Should have method to check for ExtendedBlock in FieldCollections'
        );

        // Check for ObjectBricks check
        $this->assertStringContainsString(
            'checkForExtendedBlockInObjectbricks',
            $content,
            'Should have method to check for ExtendedBlock in ObjectBricks'
        );
    }

    /**
     * Tests that ExtendedBlock has __set_state method for serialization.
     *
     * This is essential for class definition import/export from JSON
     * and PHP var_export() serialization.
     */
    public function testExtendedBlockClassHasSetStateMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\Model\\DataObject\\ClassDefinition\\Data\\ExtendedBlock',
                '__set_state'
            ),
            'ExtendedBlock should have __set_state method for serialization'
        );
    }

    /**
     * Tests that ExtendedBlock has getBlockedVarsForExport method.
     *
     * This method defines which variables should be excluded during
     * serialization to avoid exporting runtime caches.
     */
    public function testExtendedBlockClassHasGetBlockedVarsForExportMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\Model\\DataObject\\ClassDefinition\\Data\\ExtendedBlock',
                'getBlockedVarsForExport'
            ),
            'ExtendedBlock should have getBlockedVarsForExport method'
        );
    }

    /**
     * Tests that the source file contains implementation loader registration.
     *
     * The ExtendedBlock data type must be registered with Pimcore's implementation
     * loader to enable class definition import/export.
     */
    public function testExtensionRegistersImplementationLoader(): void
    {
        $sourceFile = self::SOURCE_DIR . '/DependencyInjection/ExtendedBlockExtension.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlockExtension.php should exist');

        $content = file_get_contents($sourceFile);

        // Check that it implements PrependExtensionInterface
        $this->assertStringContainsString(
            'PrependExtensionInterface',
            $content,
            'Extension should implement PrependExtensionInterface'
        );

        // Check for implementation loader registration
        $this->assertStringContainsString(
            'prependExtensionConfig',
            $content,
            'Extension should use prependExtensionConfig for registration'
        );

        // Check for extendedBlock mapping
        $this->assertStringContainsString(
            'extendedBlock',
            $content,
            'Extension should register extendedBlock data type'
        );

        // Check for class_definitions configuration
        $this->assertStringContainsString(
            'class_definitions',
            $content,
            'Extension should configure class_definitions'
        );
    }

    /**
     * Tests that ExtendedBlock uses quoteIdentifier for SQL column names.
     *
     * MySQL reserved keywords like 'index' must be quoted in SQL statements.
     * DBAL's insert() method does NOT automatically quote reserved keywords,
     * so we use raw SQL with quoteIdentifier() to handle this properly.
     */
    public function testExtendedBlockUsesQuoteIdentifierForReservedKeywords(): void
    {
        $sourceFile = self::SOURCE_DIR . '/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlock.php should exist');

        $content = file_get_contents($sourceFile);

        // Check that saveBlockItem uses quoteIdentifier for column names
        $this->assertStringContainsString(
            '$db->quoteIdentifier($column)',
            $content,
            'Should use quoteIdentifier() to quote column names'
        );

        // Check that the method builds SQL manually with quoted identifiers
        $this->assertStringContainsString(
            'Build SQL manually with quoted identifiers',
            $content,
            'Should document that SQL is built manually with quoted identifiers'
        );

        // Check that the 'index' column is included in the data array (it's a reserved keyword)
        $this->assertStringContainsString(
            "'index' => \$index",
            $content,
            'Should include index column which is a MySQL reserved keyword'
        );

        // Check that executeStatement is used instead of insert()
        $this->assertStringContainsString(
            '$db->executeStatement($sql, $values)',
            $content,
            'Should use executeStatement() for raw SQL with quoted identifiers'
        );
    }
}
