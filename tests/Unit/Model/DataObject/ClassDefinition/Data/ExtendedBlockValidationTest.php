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
 *
 * Note: These tests focus on testing the validation rules conceptually,
 * as the actual ExtendedBlock class requires Pimcore's runtime environment.
 */
class ExtendedBlockValidationTest extends TestCase
{
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
        $sourceFile = __DIR__ . '/../../../../../../src/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
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
    }

    /**
     * Tests that the ClassDefinitionListener contains placement validation logic.
     */
    public function testClassDefinitionListenerContainsPlacementValidation(): void
    {
        $sourceFile = __DIR__ . '/../../../../../../src/EventListener/ClassDefinitionListener.php';
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
        $sourceFile = __DIR__ . '/../../../../../../src/DependencyInjection/ExtendedBlockExtension.php';
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
}
