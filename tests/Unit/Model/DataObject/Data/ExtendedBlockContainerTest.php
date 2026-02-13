<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlockContainer Unit Test
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Model\DataObject\Data;

use PHPUnit\Framework\TestCase;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;

/**
 * Test cases for ExtendedBlockContainer class.
 *
 * Tests the container's ability to:
 * - Add, remove, and manage block items
 * - Implement Iterator, Countable, and ArrayAccess interfaces
 * - Filter items by type
 * - Handle lazy loading
 *
 * @covers \ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer
 */
class ExtendedBlockContainerTest extends TestCase
{
    /**
     * Tests that a new container is empty.
     *
     * @return void
     */
    public function testNewContainerIsEmpty(): void
    {
        $container = new ExtendedBlockContainer();

        $this->assertTrue($container->isEmpty());
        $this->assertCount(0, $container);
        $this->assertSame([], $container->getItems());
    }

    /**
     * Tests adding items to the container.
     *
     * @return void
     */
    public function testAddItem(): void
    {
        $container = new ExtendedBlockContainer();
        $item = new ExtendedBlockItem('test_type', 0);

        $container->addItem($item);

        $this->assertCount(1, $container);
        $this->assertFalse($container->isEmpty());
        $this->assertSame($item, $container->getItem(0));
    }

    /**
     * Tests adding multiple items.
     *
     * @return void
     */
    public function testAddMultipleItems(): void
    {
        $container = new ExtendedBlockContainer();

        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);
        $item3 = new ExtendedBlockItem('type_a', 2);

        $container->addItem($item1);
        $container->addItem($item2);
        $container->addItem($item3);

        $this->assertCount(3, $container);
        $this->assertSame(0, $container->getItem(0)->getIndex());
        $this->assertSame(1, $container->getItem(1)->getIndex());
        $this->assertSame(2, $container->getItem(2)->getIndex());
    }

    /**
     * Tests removing an item from the container.
     *
     * @return void
     */
    public function testRemoveItem(): void
    {
        $container = new ExtendedBlockContainer();
        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);

        $container->addItem($item1);
        $container->addItem($item2);
        $container->removeItem(0);

        $this->assertCount(1, $container);
        // After reindexing, item2 should be at index 0
        $this->assertSame('type_b', $container->getItem(0)->getType());
        $this->assertSame(0, $container->getItem(0)->getIndex());
    }

    /**
     * Tests the first() method.
     *
     * @return void
     */
    public function testFirst(): void
    {
        $container = new ExtendedBlockContainer();

        $this->assertNull($container->first());

        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);

        $container->addItem($item1);
        $container->addItem($item2);

        $this->assertSame($item1, $container->first());
    }

    /**
     * Tests the last() method.
     *
     * @return void
     */
    public function testLast(): void
    {
        $container = new ExtendedBlockContainer();

        $this->assertNull($container->last());

        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);

        $container->addItem($item1);
        $container->addItem($item2);

        $this->assertSame($item2, $container->last());
    }

    /**
     * Tests filtering items by type.
     *
     * @return void
     */
    public function testGetItemsByType(): void
    {
        $container = new ExtendedBlockContainer();

        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);
        $item3 = new ExtendedBlockItem('type_a', 2);

        $container->addItem($item1);
        $container->addItem($item2);
        $container->addItem($item3);

        $typeAItems = $container->getItemsByType('type_a');
        $typeBItems = $container->getItemsByType('type_b');
        $typeCItems = $container->getItemsByType('type_c');

        $this->assertCount(2, $typeAItems);
        $this->assertCount(1, $typeBItems);
        $this->assertCount(0, $typeCItems);
    }

    /**
     * Tests clearing all items from the container.
     *
     * @return void
     */
    public function testClear(): void
    {
        $container = new ExtendedBlockContainer();
        $container->addItem(new ExtendedBlockItem('type_a', 0));
        $container->addItem(new ExtendedBlockItem('type_b', 1));

        $container->clear();

        $this->assertTrue($container->isEmpty());
        $this->assertCount(0, $container);
    }

    /**
     * Tests the Iterator interface implementation.
     *
     * @return void
     */
    public function testIterator(): void
    {
        $container = new ExtendedBlockContainer();
        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);

        $container->addItem($item1);
        $container->addItem($item2);

        $items = [];
        foreach ($container as $index => $item) {
            $items[$index] = $item;
        }

        $this->assertCount(2, $items);
        $this->assertSame($item1, $items[0]);
        $this->assertSame($item2, $items[1]);
    }

    /**
     * Tests the ArrayAccess interface implementation.
     *
     * @return void
     */
    public function testArrayAccess(): void
    {
        $container = new ExtendedBlockContainer();
        $item = new ExtendedBlockItem('type_a', 0);

        // Test offsetSet with null offset (append)
        $container[] = $item;
        $this->assertTrue(isset($container[0]));
        $this->assertSame($item, $container[0]);

        // Test offsetUnset
        unset($container[0]);
        $this->assertFalse(isset($container[0]));
    }

    /**
     * Tests that ArrayAccess throws exception for invalid values.
     *
     * @return void
     */
    public function testArrayAccessWithInvalidValue(): void
    {
        $container = new ExtendedBlockContainer();

        $this->expectException(\InvalidArgumentException::class);
        $container[0] = 'invalid_value';
    }

    /**
     * Tests moving an item within the container.
     *
     * @return void
     */
    public function testMoveItem(): void
    {
        $container = new ExtendedBlockContainer();
        $item1 = new ExtendedBlockItem('type_a', 0);
        $item2 = new ExtendedBlockItem('type_b', 1);
        $item3 = new ExtendedBlockItem('type_c', 2);

        $container->addItem($item1);
        $container->addItem($item2);
        $container->addItem($item3);

        // Move first item to last position
        $container->moveItem(0, 2);

        $this->assertSame('type_b', $container->getItem(0)->getType());
        $this->assertSame('type_c', $container->getItem(1)->getType());
        $this->assertSame('type_a', $container->getItem(2)->getType());
    }

    /**
     * Tests the toArray method.
     *
     * @return void
     */
    public function testToArray(): void
    {
        $container = new ExtendedBlockContainer();
        $item1 = new ExtendedBlockItem('type_a', 0);
        $item1->setFieldValue('title', 'Test Title');

        $container->addItem($item1);

        $array = $container->toArray();

        $this->assertIsArray($array);
        $this->assertCount(1, $array);
        $this->assertSame('type_a', $array[0]['type']);
        $this->assertSame('Test Title', $array[0]['fieldValues']['title']);
    }

    /**
     * Tests setting items directly.
     *
     * @return void
     */
    public function testSetItems(): void
    {
        $container = new ExtendedBlockContainer();
        $items = [
            new ExtendedBlockItem('type_a', 0),
            new ExtendedBlockItem('type_b', 1),
        ];

        $container->setItems($items);

        $this->assertCount(2, $container);
        $this->assertSame('type_a', $container->getItem(0)->getType());
        $this->assertSame('type_b', $container->getItem(1)->getType());
    }
}
