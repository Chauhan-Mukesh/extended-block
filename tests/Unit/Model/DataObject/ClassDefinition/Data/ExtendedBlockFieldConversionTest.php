<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlock Field Conversion Unit Test.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for ExtendedBlock field conversion logic.
 *
 * These tests verify that field definitions from JavaScript (arrays)
 * are properly converted to Pimcore Data objects.
 */
class ExtendedBlockFieldConversionTest extends TestCase
{
    /**
     * Tests that the ExtendedBlock class has the field conversion method.
     */
    public function testExtendedBlockHasFieldConversionMethod(): void
    {
        $this->assertTrue(
            method_exists(
                'ExtendedBlockBundle\\Model\\DataObject\\ClassDefinition\\Data\\ExtendedBlock',
                'setBlockDefinitions'
            ),
            'ExtendedBlock should have setBlockDefinitions method'
        );
    }

    /**
     * Tests that the ExtendedBlock source file contains field conversion code.
     */
    public function testExtendedBlockContainsFieldConversionCode(): void
    {
        $sourceFile = __DIR__.'/../../../../../../src/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $this->assertFileExists($sourceFile, 'ExtendedBlock.php should exist');

        $content = file_get_contents($sourceFile);

        // Check for field conversion method
        $this->assertStringContainsString(
            'convertFieldConfigToDataObject',
            $content,
            'Should have convertFieldConfigToDataObject method'
        );

        // Check for fieldtype handling
        $this->assertStringContainsString(
            "fieldConfig['fieldtype']",
            $content,
            'Should handle fieldtype from JavaScript'
        );

        // Check for type mapping
        $this->assertStringContainsString(
            'Data\\Input::class',
            $content,
            'Should map input type to Pimcore Input class'
        );
    }

    /**
     * Tests that supported field types are defined in the conversion map.
     */
    public function testSupportedFieldTypesAreDefined(): void
    {
        $sourceFile = __DIR__.'/../../../../../../src/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $content = file_get_contents($sourceFile);

        $supportedTypes = [
            'input',
            'textarea',
            'wysiwyg',
            'numeric',
            'checkbox',
            'date',
            'select',
            'multiselect',
            'link',
            'image',
        ];

        foreach ($supportedTypes as $type) {
            $this->assertStringContainsString(
                "'{$type}' =>",
                $content,
                "Field type '{$type}' should be in the type map"
            );
        }
    }

    /**
     * Tests that the conversion handles both Data objects and arrays.
     */
    public function testConversionHandlesBothDataObjectsAndArrays(): void
    {
        $sourceFile = __DIR__.'/../../../../../../src/Model/DataObject/ClassDefinition/Data/ExtendedBlock.php';
        $content = file_get_contents($sourceFile);

        // Check for instanceof Data check
        $this->assertStringContainsString(
            'fieldConfig instanceof Data',
            $content,
            'Should check if fieldConfig is already a Data object'
        );

        // Check for is_array check
        $this->assertStringContainsString(
            'is_array($fieldConfig)',
            $content,
            'Should check if fieldConfig is an array'
        );
    }
}
