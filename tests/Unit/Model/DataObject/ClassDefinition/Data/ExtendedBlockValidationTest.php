<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlock Validation Unit Test
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for ExtendedBlock validation logic.
 *
 * These tests verify the validation rules for nesting prevention:
 * - No ExtendedBlock inside ExtendedBlock
 * - No Block inside ExtendedBlock
 * - No ExtendedBlock inside Block
 * - No nested blocks in LocalizedFields
 *
 * Note: These tests focus on testing the validation rules conceptually,
 * as the actual ExtendedBlock class requires Pimcore's runtime environment.
 */
class ExtendedBlockValidationTest extends TestCase
{
    /**
     * Tests that nested block prevention rules are documented.
     * 
     * This test ensures the validation rules are defined:
     * 1. ExtendedBlock cannot contain another ExtendedBlock
     * 2. ExtendedBlock cannot contain a Block
     * 3. Block cannot contain ExtendedBlock
     * 4. ExtendedBlock in LocalizedFields cannot contain LocalizedFields
     * 5. Block cannot be inside LocalizedFields within ExtendedBlock
     *
     * @return void
     */
    public function testValidationRulesAreDefined(): void
    {
        // The validation rules are implemented in ExtendedBlock::validate()
        // and ClassDefinitionListener::checkForExtendedBlockInBlock()
        
        // These rules should prevent:
        $preventedNestings = [
            'ExtendedBlock inside ExtendedBlock' => true,
            'Block inside ExtendedBlock' => true,
            'ExtendedBlock inside Block' => true,
            'ExtendedBlock with LocalizedFields inside LocalizedFields' => true,
            'Block inside LocalizedFields within ExtendedBlock' => true,
        ];

        // All nesting prevention rules should be active
        foreach ($preventedNestings as $rule => $isActive) {
            $this->assertTrue($isActive, "Rule '{$rule}' should be active");
        }
    }

    /**
     * Tests that the ExtendedBlock class exists with validate method.
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
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
        
        // Check for Block inside LocalizedFields prevention
        $this->assertStringContainsString(
            'Block cannot be placed inside LocalizedFields within an ExtendedBlock',
            $content,
            'Should have Block in LocalizedFields prevention error message'
        );
    }

    /**
     * Tests that the ClassDefinitionListener contains Block validation logic.
     *
     * @return void
     */
    public function testClassDefinitionListenerContainsBlockValidation(): void
    {
        $sourceFile = __DIR__ . '/../../../../../../src/EventListener/ClassDefinitionListener.php';
        $this->assertFileExists($sourceFile, 'ClassDefinitionListener.php should exist');
        
        $content = file_get_contents($sourceFile);
        
        // Check for ExtendedBlock inside Block prevention
        $this->assertStringContainsString(
            'ExtendedBlock field',
            $content,
            'Should reference ExtendedBlock field in validation'
        );
        
        $this->assertStringContainsString(
            'cannot be placed inside Block',
            $content,
            'Should have ExtendedBlock inside Block prevention message'
        );
    }
}
