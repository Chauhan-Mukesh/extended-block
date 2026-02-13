<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Extended Block Container
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Model\DataObject\Data;

use Pimcore\Model\DataObject\Concrete;
use ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data\ExtendedBlock;

/**
 * Container class for Extended Block items.
 *
 * This class holds a collection of ExtendedBlockItem instances and provides
 * methods for managing, iterating, and manipulating block items.
 *
 * Features:
 * - Lazy loading support for improved performance
 * - Iterator implementation for foreach loops
 * - Countable implementation for count() function
 * - ArrayAccess for array-like access to items
 *
 * Usage:
 * ```php
 * $container = $object->getExtendedBlockField();
 * foreach ($container as $item) {
 *     echo $item->getType();
 * }
 *
 * // Or with array access
 * $firstItem = $container[0];
 * $count = count($container);
 * ```
 *
 * @implements \Iterator<int, ExtendedBlockItem>
 * @implements \ArrayAccess<int, ExtendedBlockItem>
 */
class ExtendedBlockContainer implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * The parent object that owns this container.
     *
     * @var Concrete|null
     */
    protected ?Concrete $object = null;

    /**
     * The field name of the extended block in the parent object.
     *
     * @var string
     */
    protected string $fieldname = '';

    /**
     * The extended block definition.
     *
     * @var ExtendedBlock|null
     */
    protected ?ExtendedBlock $definition = null;

    /**
     * Array of block items in this container.
     *
     * @var array<int, ExtendedBlockItem>
     */
    protected array $items = [];

    /**
     * Whether lazy loading is enabled.
     *
     * @var bool
     */
    protected bool $lazyLoad = false;

    /**
     * Whether data has been loaded (for lazy loading).
     *
     * @var bool
     */
    protected bool $loaded = false;

    /**
     * Current iterator position.
     *
     * @var int
     */
    protected int $position = 0;

    /**
     * Creates a new ExtendedBlockContainer.
     *
     * @param Concrete|null      $object     The parent object
     * @param string             $fieldname  The field name
     * @param ExtendedBlock|null $definition The block definition
     * @param bool               $lazyLoad   Whether to use lazy loading
     */
    public function __construct(
        ?Concrete $object = null,
        string $fieldname = '',
        ?ExtendedBlock $definition = null,
        bool $lazyLoad = false
    ) {
        $this->object = $object;
        $this->fieldname = $fieldname;
        $this->definition = $definition;
        $this->lazyLoad = $lazyLoad;
        $this->loaded = !$lazyLoad;
    }

    /**
     * Ensures data is loaded (for lazy loading support).
     *
     * If lazy loading is enabled and data hasn't been loaded yet,
     * this method triggers the data loading from the database.
     *
     * @return void
     */
    protected function ensureLoaded(): void
    {
        if ($this->loaded || !$this->lazyLoad) {
            return;
        }

        if ($this->definition && $this->object) {
            $loadedContainer = $this->definition->loadBlockData($this->object);
            $this->items = $loadedContainer->getItems();
        }

        $this->loaded = true;
    }

    /**
     * Returns all items in the container.
     *
     * @return array<int, ExtendedBlockItem> Array of block items
     */
    public function getItems(): array
    {
        $this->ensureLoaded();
        return $this->items;
    }

    /**
     * Sets all items in the container.
     *
     * @param array<int, ExtendedBlockItem> $items The items to set
     *
     * @return static
     */
    public function setItems(array $items): static
    {
        $this->items = $items;
        $this->loaded = true;
        $this->reindex();
        return $this;
    }

    /**
     * Adds an item to the container.
     *
     * @param ExtendedBlockItem $item The item to add
     *
     * @return static
     */
    public function addItem(ExtendedBlockItem $item): static
    {
        $this->ensureLoaded();
        $item->setIndex(count($this->items));
        $item->setObject($this->object);
        $item->setFieldname($this->fieldname);
        $this->items[] = $item;
        return $this;
    }

    /**
     * Removes an item at the specified index.
     *
     * @param int $index The index of the item to remove
     *
     * @return static
     */
    public function removeItem(int $index): static
    {
        $this->ensureLoaded();

        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->reindex();
        }

        return $this;
    }

    /**
     * Gets an item at the specified index.
     *
     * @param int $index The index of the item
     *
     * @return ExtendedBlockItem|null The item or null if not found
     */
    public function getItem(int $index): ?ExtendedBlockItem
    {
        $this->ensureLoaded();
        return $this->items[$index] ?? null;
    }

    /**
     * Moves an item from one position to another.
     *
     * @param int $fromIndex The current position
     * @param int $toIndex   The target position
     *
     * @return static
     */
    public function moveItem(int $fromIndex, int $toIndex): static
    {
        $this->ensureLoaded();

        if (!isset($this->items[$fromIndex])) {
            return $this;
        }

        $item = $this->items[$fromIndex];
        unset($this->items[$fromIndex]);

        // Reindex first
        $this->items = array_values($this->items);

        // Insert at new position
        array_splice($this->items, $toIndex, 0, [$item]);

        $this->reindex();

        return $this;
    }

    /**
     * Re-indexes all items to ensure sequential indices.
     *
     * @return void
     */
    protected function reindex(): void
    {
        $this->items = array_values($this->items);
        foreach ($this->items as $index => $item) {
            $item->setIndex($index);
        }
    }

    /**
     * Returns items filtered by type.
     *
     * @param string $type The block type to filter by
     *
     * @return array<int, ExtendedBlockItem> Filtered items
     */
    public function getItemsByType(string $type): array
    {
        $this->ensureLoaded();

        return array_filter($this->items, fn($item) => $item->getType() === $type);
    }

    /**
     * Clears all items from the container.
     *
     * @return static
     */
    public function clear(): static
    {
        $this->items = [];
        $this->loaded = true;
        return $this;
    }

    /**
     * Checks if the container is empty.
     *
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        $this->ensureLoaded();
        return empty($this->items);
    }

    /**
     * Returns the first item or null.
     *
     * @return ExtendedBlockItem|null The first item
     */
    public function first(): ?ExtendedBlockItem
    {
        $this->ensureLoaded();
        return $this->items[0] ?? null;
    }

    /**
     * Returns the last item or null.
     *
     * @return ExtendedBlockItem|null The last item
     */
    public function last(): ?ExtendedBlockItem
    {
        $this->ensureLoaded();
        $count = count($this->items);
        return $count > 0 ? $this->items[$count - 1] : null;
    }

    // Iterator interface implementation

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        $this->ensureLoaded();
        return $this->items[$this->position] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        $this->ensureLoaded();
        return isset($this->items[$this->position]);
    }

    // Countable interface implementation

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        $this->ensureLoaded();
        return count($this->items);
    }

    // ArrayAccess interface implementation

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        $this->ensureLoaded();
        return isset($this->items[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        $this->ensureLoaded();
        return $this->items[$offset] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->ensureLoaded();

        if (!$value instanceof ExtendedBlockItem) {
            throw new \InvalidArgumentException('Value must be an instance of ExtendedBlockItem');
        }

        if ($offset === null) {
            $this->addItem($value);
        } else {
            $value->setIndex($offset);
            $value->setObject($this->object);
            $value->setFieldname($this->fieldname);
            $this->items[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->removeItem($offset);
    }

    // Getters and Setters

    /**
     * @return Concrete|null
     */
    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    /**
     * @param Concrete|null $object
     * @return static
     */
    public function setObject(?Concrete $object): static
    {
        $this->object = $object;
        return $this;
    }

    /**
     * @return string
     */
    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    /**
     * @param string $fieldname
     * @return static
     */
    public function setFieldname(string $fieldname): static
    {
        $this->fieldname = $fieldname;
        return $this;
    }

    /**
     * @return ExtendedBlock|null
     */
    public function getDefinition(): ?ExtendedBlock
    {
        return $this->definition;
    }

    /**
     * @param ExtendedBlock|null $definition
     * @return static
     */
    public function setDefinition(?ExtendedBlock $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLazyLoad(): bool
    {
        return $this->lazyLoad;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    /**
     * Converts the container to an array representation.
     *
     * @return array<int, array<string, mixed>> Array representation
     */
    public function toArray(): array
    {
        $this->ensureLoaded();

        return array_map(fn($item) => $item->toArray(), $this->items);
    }
}
