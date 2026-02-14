<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Extended Block Item.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Model\DataObject\Data;

use Pimcore\Model\DataObject\Concrete;

/**
 * Represents a single item within an Extended Block.
 *
 * Each ExtendedBlockItem is stored as a row in the extended block table,
 * with optional localized data stored in a separate table.
 *
 * This class provides:
 * - Storage for all field values defined in the block type
 * - Localized field data management
 * - Type identification for block variations
 * - Index tracking for ordering within the container
 *
 * This class is marked as final to ensure type safety with static factory methods.
 *
 * Usage:
 * ```php
 * $item = new ExtendedBlockItem('text_block', 0);
 * $item->setFieldValue('title', 'Hello World');
 * $item->setFieldValue('content', '<p>Some content</p>');
 *
 * // For localized data
 * $item->setLocalizedValue('en', 'title', 'Hello');
 * $item->setLocalizedValue('de', 'title', 'Hallo');
 * ```
 *
 * @see ExtendedBlockContainer
 */
final class ExtendedBlockItem
{
    /**
     * Database ID for this item.
     *
     * This is set after the item is saved to the database.
     */
    private ?int $id = null;

    /**
     * Block type identifier.
     *
     * Corresponds to the block type defined in the ExtendedBlock definition.
     * E.g., 'text_block', 'image_block', 'video_block'
     */
    private string $type = 'default';

    /**
     * Position index within the container.
     *
     * Used for ordering block items. Index starts at 0.
     */
    private int $index = 0;

    /**
     * Reference to the parent object.
     */
    private ?Concrete $object = null;

    /**
     * Field name of the extended block in the parent object.
     */
    private string $fieldname = '';

    /**
     * Storage for non-localized field values.
     *
     * Structure: ['field_name' => value, ...]
     *
     * @var array<string, mixed>
     */
    private array $fieldValues = [];

    /**
     * Storage for localized field values.
     *
     * Structure: ['language' => ['field_name' => value, ...], ...]
     *
     * @var array<string, array<string, mixed>>
     */
    private array $localizedData = [];

    /**
     * Marks this item as modified and needing save.
     */
    private bool $modified = false;

    /**
     * Creates a new ExtendedBlockItem.
     *
     * @param string        $type      The block type identifier
     * @param int           $index     The position index
     * @param Concrete|null $object    The parent object
     * @param string        $fieldname The field name
     */
    public function __construct(
        string $type = 'default',
        int $index = 0,
        ?Concrete $object = null,
        string $fieldname = '',
    ) {
        $this->type = $type;
        $this->index = $index;
        $this->object = $object;
        $this->fieldname = $fieldname;
    }

    /**
     * Magic getter for field values.
     *
     * Allows accessing field values as properties:
     * ```php
     * $item->title; // equivalent to $item->getFieldValue('title');
     * ```
     *
     * @param string $name The field name
     *
     * @return mixed The field value
     */
    public function __get(string $name): mixed
    {
        return $this->getFieldValue($name);
    }

    /**
     * Magic setter for field values.
     *
     * Allows setting field values as properties:
     * ```php
     * $item->title = 'Hello'; // equivalent to $item->setFieldValue('title', 'Hello');
     * ```
     *
     * @param string $name  The field name
     * @param mixed  $value The value
     */
    public function __set(string $name, mixed $value): void
    {
        $this->setFieldValue($name, $value);
    }

    /**
     * Magic isset for field values.
     *
     * @param string $name The field name
     *
     * @return bool True if field exists
     */
    public function __isset(string $name): bool
    {
        return $this->hasFieldValue($name);
    }

    /**
     * Magic unset for field values.
     *
     * @param string $name The field name
     */
    public function __unset(string $name): void
    {
        $this->removeFieldValue($name);
    }

    /**
     * Creates an item from array data.
     *
     * @param array<string, mixed> $data      The array data
     * @param Concrete|null        $object    The parent object
     * @param string               $fieldname The field name
     *
     * @return static The created item
     */
    public static function fromArray(array $data, ?Concrete $object = null, string $fieldname = ''): static
    {
        $item = new static(
            type: $data['type'] ?? 'default',
            index: $data['index'] ?? 0,
            object: $object,
            fieldname: $fieldname
        );

        if (isset($data['id'])) {
            $item->setId((int) $data['id']);
        }

        if (isset($data['fieldValues']) && is_array($data['fieldValues'])) {
            foreach ($data['fieldValues'] as $name => $value) {
                $item->setFieldValue($name, $value);
            }
        }

        if (isset($data['localizedData']) && is_array($data['localizedData'])) {
            $item->setLocalizedData($data['localizedData']);
        }

        $item->setModified(false);

        return $item;
    }

    /**
     * Gets a field value by name.
     *
     * @param string $name The field name
     *
     * @return mixed The field value or null if not set
     */
    public function getFieldValue(string $name): mixed
    {
        return $this->fieldValues[$name] ?? null;
    }

    /**
     * Sets a field value by name.
     *
     * @param string $name  The field name
     * @param mixed  $value The value to set
     */
    public function setFieldValue(string $name, mixed $value): static
    {
        $this->fieldValues[$name] = $value;
        $this->modified = true;

        return $this;
    }

    /**
     * Checks if a field value exists.
     *
     * @param string $name The field name
     *
     * @return bool True if the field exists
     */
    public function hasFieldValue(string $name): bool
    {
        return array_key_exists($name, $this->fieldValues);
    }

    /**
     * Removes a field value.
     *
     * @param string $name The field name
     */
    public function removeFieldValue(string $name): static
    {
        unset($this->fieldValues[$name]);
        $this->modified = true;

        return $this;
    }

    /**
     * Gets all field values.
     *
     * @return array<string, mixed> All field values
     */
    public function getAllFieldValues(): array
    {
        return $this->fieldValues;
    }

    /**
     * Gets a localized field value.
     *
     * @param string $language The language code (e.g., 'en', 'de')
     * @param string $name     The field name
     *
     * @return mixed The localized value or null if not set
     */
    public function getLocalizedValue(string $language, string $name): mixed
    {
        return $this->localizedData[$language][$name] ?? null;
    }

    /**
     * Sets a localized field value.
     *
     * @param string $language The language code
     * @param string $name     The field name
     * @param mixed  $value    The value to set
     */
    public function setLocalizedValue(string $language, string $name, mixed $value): static
    {
        if (!isset($this->localizedData[$language])) {
            $this->localizedData[$language] = [];
        }

        $this->localizedData[$language][$name] = $value;
        $this->modified = true;

        return $this;
    }

    /**
     * Gets all localized values for a specific language.
     *
     * @param string $language The language code
     *
     * @return array<string, mixed> Localized values for the language
     */
    public function getLocalizedValuesForLanguage(string $language): array
    {
        return $this->localizedData[$language] ?? [];
    }

    /**
     * Gets all localized data for all languages.
     *
     * @return array<string, array<string, mixed>> All localized data
     */
    public function getLocalizedData(): array
    {
        return $this->localizedData;
    }

    /**
     * Sets all localized data.
     *
     * @param array<string, array<string, mixed>> $data The localized data
     */
    public function setLocalizedData(array $data): static
    {
        $this->localizedData = $data;
        $this->modified = true;

        return $this;
    }

    /**
     * Checks if localized data exists for a language.
     *
     * @param string      $language The language code
     * @param string|null $name     Optional field name
     *
     * @return bool True if localized data exists
     */
    public function hasLocalizedData(string $language, ?string $name = null): bool
    {
        if (!isset($this->localizedData[$language])) {
            return false;
        }

        if (null !== $name) {
            return array_key_exists($name, $this->localizedData[$language]);
        }

        return true;
    }

    /**
     * Clears all localized data for a language.
     *
     * @param string $language The language code
     */
    public function clearLocalizedData(string $language): static
    {
        unset($this->localizedData[$language]);
        $this->modified = true;

        return $this;
    }

    /**
     * Converts the item to an array representation.
     *
     * @return array<string, mixed> Array representation
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'index' => $this->index,
            'fieldValues' => $this->fieldValues,
            'localizedData' => $this->localizedData,
        ];
    }

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        $this->modified = true;

        return $this;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function setIndex(int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    public function setObject(?Concrete $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getFieldname(): string
    {
        return $this->fieldname;
    }

    public function setFieldname(string $fieldname): static
    {
        $this->fieldname = $fieldname;

        return $this;
    }

    public function isModified(): bool
    {
        return $this->modified;
    }

    public function setModified(bool $modified): static
    {
        $this->modified = $modified;

        return $this;
    }
}
