<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlockItem Unit Test.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Model\DataObject\Data;

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for ExtendedBlockItem class.
 *
 * Tests the item's ability to:
 * - Store and retrieve field values
 * - Handle localized data
 * - Use magic getters/setters
 * - Convert to/from array format
 *
 * @covers \ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem
 */
class ExtendedBlockItemTest extends TestCase
{
    /**
     * Tests creating an item with default values.
     */
    public function testDefaultValues(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertSame('default', $item->getType());
        $this->assertSame(0, $item->getIndex());
        $this->assertNull($item->getId());
        $this->assertNull($item->getObject());
        $this->assertSame('', $item->getFieldname());
        $this->assertSame([], $item->getAllFieldValues());
        $this->assertSame([], $item->getLocalizedData());
    }

    /**
     * Tests creating an item with custom values.
     */
    public function testCustomValues(): void
    {
        $item = new ExtendedBlockItem(
            type: 'custom_type',
            index: 5
        );

        $this->assertSame('custom_type', $item->getType());
        $this->assertSame(5, $item->getIndex());
    }

    /**
     * Tests setting and getting field values.
     */
    public function testFieldValues(): void
    {
        $item = new ExtendedBlockItem();

        $item->setFieldValue('title', 'Test Title');
        $item->setFieldValue('content', 'Test Content');
        $item->setFieldValue('count', 42);

        $this->assertSame('Test Title', $item->getFieldValue('title'));
        $this->assertSame('Test Content', $item->getFieldValue('content'));
        $this->assertSame(42, $item->getFieldValue('count'));
        $this->assertNull($item->getFieldValue('nonexistent'));

        $this->assertTrue($item->hasFieldValue('title'));
        $this->assertFalse($item->hasFieldValue('nonexistent'));
    }

    /**
     * Tests removing a field value.
     */
    public function testRemoveFieldValue(): void
    {
        $item = new ExtendedBlockItem();
        $item->setFieldValue('title', 'Test');

        $this->assertTrue($item->hasFieldValue('title'));

        $item->removeFieldValue('title');

        $this->assertFalse($item->hasFieldValue('title'));
        $this->assertNull($item->getFieldValue('title'));
    }

    /**
     * Tests magic getter and setter.
     */
    public function testMagicMethods(): void
    {
        $item = new ExtendedBlockItem();

        // Magic setter
        $item->title = 'Magic Title';
        $item->content = 'Magic Content';

        // Magic getter
        $this->assertSame('Magic Title', $item->title);
        $this->assertSame('Magic Content', $item->content);

        // Magic isset
        $this->assertTrue(isset($item->title));
        $this->assertFalse(isset($item->nonexistent));

        // Magic unset
        unset($item->title);
        $this->assertFalse(isset($item->title));
    }

    /**
     * Tests localized data handling.
     */
    public function testLocalizedData(): void
    {
        $item = new ExtendedBlockItem();

        $item->setLocalizedValue('en', 'title', 'English Title');
        $item->setLocalizedValue('de', 'title', 'German Title');
        $item->setLocalizedValue('en', 'description', 'English Description');

        $this->assertSame('English Title', $item->getLocalizedValue('en', 'title'));
        $this->assertSame('German Title', $item->getLocalizedValue('de', 'title'));
        $this->assertSame('English Description', $item->getLocalizedValue('en', 'description'));
        $this->assertNull($item->getLocalizedValue('fr', 'title'));

        $this->assertTrue($item->hasLocalizedData('en'));
        $this->assertTrue($item->hasLocalizedData('en', 'title'));
        $this->assertFalse($item->hasLocalizedData('fr'));
        $this->assertFalse($item->hasLocalizedData('en', 'nonexistent'));
    }

    /**
     * Tests getting localized values for a specific language.
     */
    public function testGetLocalizedValuesForLanguage(): void
    {
        $item = new ExtendedBlockItem();

        $item->setLocalizedValue('en', 'title', 'English Title');
        $item->setLocalizedValue('en', 'description', 'English Description');

        $enValues = $item->getLocalizedValuesForLanguage('en');
        $frValues = $item->getLocalizedValuesForLanguage('fr');

        $this->assertCount(2, $enValues);
        $this->assertSame('English Title', $enValues['title']);
        $this->assertSame('English Description', $enValues['description']);
        $this->assertSame([], $frValues);
    }

    /**
     * Tests clearing localized data for a language.
     */
    public function testClearLocalizedData(): void
    {
        $item = new ExtendedBlockItem();

        $item->setLocalizedValue('en', 'title', 'English Title');
        $item->setLocalizedValue('de', 'title', 'German Title');

        $item->clearLocalizedData('en');

        $this->assertFalse($item->hasLocalizedData('en'));
        $this->assertTrue($item->hasLocalizedData('de'));
    }

    /**
     * Tests setting all localized data at once.
     */
    public function testSetLocalizedData(): void
    {
        $item = new ExtendedBlockItem();

        $localizedData = [
            'en' => ['title' => 'English', 'content' => 'Content EN'],
            'de' => ['title' => 'Deutsch', 'content' => 'Content DE'],
        ];

        $item->setLocalizedData($localizedData);

        $this->assertSame('English', $item->getLocalizedValue('en', 'title'));
        $this->assertSame('Deutsch', $item->getLocalizedValue('de', 'title'));
    }

    /**
     * Tests converting item to array.
     */
    public function testToArray(): void
    {
        $item = new ExtendedBlockItem('test_type', 3);
        $item->setId(42);
        $item->setFieldValue('title', 'Test');
        $item->setLocalizedValue('en', 'name', 'English Name');

        $array = $item->toArray();

        $this->assertSame(42, $array['id']);
        $this->assertSame('test_type', $array['type']);
        $this->assertSame(3, $array['index']);
        $this->assertSame('Test', $array['fieldValues']['title']);
        $this->assertSame('English Name', $array['localizedData']['en']['name']);
    }

    /**
     * Tests creating item from array.
     */
    public function testFromArray(): void
    {
        $data = [
            'id' => 42,
            'type' => 'custom_type',
            'index' => 5,
            'fieldValues' => [
                'title' => 'Test Title',
                'content' => 'Test Content',
            ],
            'localizedData' => [
                'en' => ['name' => 'English'],
                'de' => ['name' => 'Deutsch'],
            ],
        ];

        $item = ExtendedBlockItem::fromArray($data);

        $this->assertSame(42, $item->getId());
        $this->assertSame('custom_type', $item->getType());
        $this->assertSame(5, $item->getIndex());
        $this->assertSame('Test Title', $item->getFieldValue('title'));
        $this->assertSame('English', $item->getLocalizedValue('en', 'name'));
    }

    /**
     * Tests the modified flag.
     */
    public function testModifiedFlag(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertFalse($item->isModified());

        $item->setFieldValue('title', 'Test');
        $this->assertTrue($item->isModified());

        $item->setModified(false);
        $this->assertFalse($item->isModified());

        $item->setType('new_type');
        $this->assertTrue($item->isModified());
    }

    /**
     * Tests ID getter and setter.
     */
    public function testId(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertNull($item->getId());

        $item->setId(123);
        $this->assertSame(123, $item->getId());

        $item->setId(null);
        $this->assertNull($item->getId());
    }

    /**
     * Tests type getter and setter.
     */
    public function testType(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertSame('default', $item->getType());

        $item->setType('custom');
        $this->assertSame('custom', $item->getType());
    }

    /**
     * Tests index getter and setter.
     */
    public function testIndex(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertSame(0, $item->getIndex());

        $item->setIndex(10);
        $this->assertSame(10, $item->getIndex());
    }

    /**
     * Tests fieldname getter and setter.
     */
    public function testFieldname(): void
    {
        $item = new ExtendedBlockItem();

        $this->assertSame('', $item->getFieldname());

        $item->setFieldname('myField');
        $this->assertSame('myField', $item->getFieldname());
    }

    /**
     * Tests getting all field values.
     */
    public function testGetAllFieldValues(): void
    {
        $item = new ExtendedBlockItem();

        $item->setFieldValue('field1', 'value1');
        $item->setFieldValue('field2', 'value2');
        $item->setFieldValue('field3', 'value3');

        $allValues = $item->getAllFieldValues();

        $this->assertCount(3, $allValues);
        $this->assertSame('value1', $allValues['field1']);
        $this->assertSame('value2', $allValues['field2']);
        $this->assertSame('value3', $allValues['field3']);
    }
}
