<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - ExtendedBlock Data Type Definition.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data;

use DateTimeInterface;
use Exception;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use ExtendedBlockBundle\Service\IdentifierValidator;
use InvalidArgumentException;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\QueryResourcePersistenceAwareInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Data\Link;
use Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData as FieldcollectionAbstract;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData as ObjectbrickAbstract;
use Pimcore\Model\Element;
use RuntimeException;

/**
 * Extended Block Data Type Definition.
 *
 * This class defines the ExtendedBlock data type for Pimcore class definitions.
 * Unlike the standard Block type that stores data as serialized JSON in a single column,
 * ExtendedBlock stores each block item in a separate database table row, providing:
 *
 * - Better query performance and indexability
 * - Proper relational data model
 * - Per-item localized data storage (via ExtendedBlockItem)
 * - Easier data migration and manipulation
 *
 * Database Structure:
 * - Main table: object_eb_{classId}_{fieldName}
 *   - id (PK)
 *   - o_id (FK to object)
 *   - fieldname
 *   - index (position in block)
 *   - type (block item type)
 *   - ... (all field columns)
 *
 * - Localized table: object_eb_{classId}_{fieldName}_localized
 *   - id (PK)
 *   - ooo_id (FK to main table)
 *   - language
 *   - ... (localized field columns)
 *
 * @see Data
 */
class ExtendedBlock extends Data implements Data\CustomResourcePersistingInterface, Data\LazyLoadingSupportInterface, Data\LayoutDefinitionEnrichmentInterface, Data\PreGetDataInterface, Data\VarExporterInterface
{
    /**
     * Maximum number of items to show in grid preview.
     */
    private const GRID_MAX_PREVIEW_ITEMS = 5;

    /**
     * Maximum length for truncated string values in grid.
     */
    private const GRID_MAX_STRING_LENGTH = 50;

    /**
     * Length of truncated string prefix (GRID_MAX_STRING_LENGTH - 3 for "...").
     */
    private const GRID_TRUNCATE_LENGTH = 47;

    /*
     * =========================================================================
     * RELATIONAL FIELD SUPPORT MATRIX
     * =========================================================================
     *
     * SAFE - Directly Supported (store as simple ID reference):
     * - ManyToOneRelation      - Stores as single ID + type in main table
     * - Input, Textarea, Date, DateTime, Numeric, etc. - Simple scalar values
     *
     * CONDITIONALLY SAFE - Supported with care:
     * - ManyToManyRelation, ManyToManyObjectRelation - Store as comma-delimited IDs
     *   Note: These use separate relation tables in Pimcore, but ExtendedBlock
     *   serializes them as text using getDataForResource().
     *
     * UNSAFE - Explicitly Blocked:
     * - AdvancedManyToManyRelation      - Complex metadata per relation
     * - AdvancedManyToManyObjectRelation - Complex metadata per relation
     * - ReverseObjectRelation           - Virtual field, reads owner's relations
     * - Fieldcollections                - Multi-table container
     * - Objectbricks                    - Multi-table container
     * - Classificationstore             - Multi-table structure
     * - Block                           - Nested container
     * - ExtendedBlock                   - Self-nesting forbidden
     * - LocalizedFields                 - Complex localization handling
     *
     * =========================================================================
     */

    /**
     * Data type identifier for Pimcore.
     */
    public string $fieldtype = 'extendedBlock';

    /**
     * Layout definition containing the block field definitions.
     *
     * This defines what fields are available within each block item.
     *
     * @var array<Layout|Data>
     */
    public array $layoutDefinitions = [];

    /**
     * Block type definitions.
     *
     * Defines the available block types and their respective field configurations.
     * Structure:
     * ```
     * [
     *     'type_name' => [
     *         'name' => 'Type Display Name',
     *         'icon' => 'icon-class',
     *         'fields' => [...Layout/Data definitions...]
     *     ]
     * ]
     * ```
     *
     * @var array<string, array<string, mixed>>
     */
    public array $blockDefinitions = [];

    /**
     * Maximum number of block items allowed.
     *
     * Null means unlimited items are allowed.
     */
    public ?int $maxItems = null;

    /**
     * Minimum number of block items required.
     */
    public int $minItems = 0;

    /**
     * Whether block items can be collapsed in the admin UI.
     */
    public bool $collapsible = true;

    /**
     * Whether block items should be collapsed by default.
     */
    public bool $collapsed = false;

    /**
     * Whether to enable lazy loading of block items.
     *
     * When enabled, block items are only loaded when accessed,
     * improving performance for objects with many blocks.
     */
    public bool $lazyLoading = true;

    /**
     * Whether adding/removing block items is disallowed.
     */
    public bool $disallowAddRemove = false;

    /**
     * Whether reordering block items is disallowed.
     */
    public bool $disallowReorder = false;

    /**
     * Custom CSS style for block element (e.g., "float: left; margin: 10px;").
     */
    public ?string $styleElement = null;

    /**
     * Whether to allow localized fields inside this block.
     */
    public bool $allowLocalizedFields = false;

    /**
     * Flag to indicate this block should not be added inside LocalizedFields.
     * Set when the block contains localized fields itself.
     */
    public bool $disallowAddingInLocalizedField = false;

    /**
     * Child field definitions (following Pimcore Block pattern).
     *
     * @var array<Data|Layout>
     */
    public array $children = [];

    /**
     * Cached field definitions for quick lookup.
     *
     * @var array<string, Data>|null
     */
    protected ?array $fieldDefinitionsCache = null;

    /**
     * Database table prefix for this extended block.
     */
    protected string $tablePrefix = 'object_eb_';

    /**
     * Returns the list of property names to serialize.
     *
     * Excludes runtime caches and computed values that should
     * be rebuilt on deserialization.
     *
     * @return array<string> List of property names to serialize
     */
    public function __sleep(): array
    {
        $vars = get_object_vars($this);
        $blockedVars = $this->getBlockedVarsForExport();

        foreach ($blockedVars as $blockedVar) {
            unset($vars[$blockedVar]);
        }

        return array_keys($vars);
    }

    /**
     * Creates an instance from exported array data.
     *
     * This method is called by PHP when using var_export() to serialize
     * the class definition. It properly reconstructs the ExtendedBlock
     * instance including any nested child definitions.
     *
     * @param array<string, mixed> $data The exported data array
     *
     * @return static The reconstructed ExtendedBlock instance
     */
    public static function __set_state(array $data): static
    {
        $obj = new static();
        $obj->setValues($data);

        return $obj;
    }

    /**
     * Returns the data type name for display.
     *
     * @return string The type name
     */
    public function getTypeName(): string
    {
        return 'Extended Block';
    }

    /**
     * Returns the field type identifier.
     *
     * @return string The field type
     */
    public function getFieldType(): string
    {
        return 'extendedBlock';
    }

    /**
     * Returns the PHPDoc type hint for input values.
     *
     * @return string|null The input type
     */
    public function getPhpdocInputType(): ?string
    {
        return '\\' . ExtendedBlockContainer::class . '|null';
    }

    /**
     * Returns the PHPDoc type hint for return values.
     *
     * @return string|null The return type
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\' . ExtendedBlockContainer::class . '|null';
    }

    /**
     * Returns the column type for the main object table.
     *
     * Since data is stored in separate tables, no column is needed in the main table.
     * We only store metadata/reference information.
     *
     * @return string|null The column type or null
     */
    public function getColumnType(): ?string
    {
        return null;
    }

    /**
     * Returns the query column type for listing queries.
     *
     * ExtendedBlock stores data in separate tables, so returns empty array.
     *
     * @return array<string, string>|string The query column type
     */
    public function getQueryColumnType(): array|string
    {
        return [];
    }

    /**
     * Returns data for query resource.
     *
     * ExtendedBlock doesn't store data in the query table, returns null.
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return mixed The query resource data (null as data is stored separately)
     */
    public function getDataForQueryResource(mixed $data, ?Concrete $object = null, array $params = []): mixed
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * ExtendedBlock stores data in separate tables, not in the main query table.
     */
    public function getQueryColumnDefinition(): ?string
    {
        return null;
    }

    /**
     * Transforms the data from the object to the format stored in database.
     *
     * This method is called when saving an object. It extracts the block data
     * and prepares it for storage in the separate extended block tables.
     *
     * @param mixed                $data   The block data (ExtendedBlockContainer or array)
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return mixed The transformed data (null as actual data is stored separately)
     */
    public function getDataForResource(mixed $data, ?Concrete $object = null, array $params = []): mixed
    {
        // Data is stored in separate tables via the save() hook
        // Return null as no data goes into the main object table
        return null;
    }

    /**
     * Transforms the data from database format to object format.
     *
     * This method is called when loading an object. It retrieves block data
     * from the separate extended block tables.
     *
     * @param mixed                $data   The raw database data (usually null)
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return ExtendedBlockContainer|null The block container with items
     */
    public function getDataFromResource(mixed $data, ?Concrete $object = null, array $params = []): ?ExtendedBlockContainer
    {
        if (!$object instanceof Concrete) {
            return null;
        }

        // Lazy loading: return a container that loads data on demand
        if ($this->lazyLoading) {
            return new ExtendedBlockContainer(
                object: $object,
                fieldname: $this->getName(),
                definition: $this,
                lazyLoad: true
            );
        }

        // Eager loading: load all items immediately
        return $this->loadBlockData($object);
    }

    /**
     * Loads block data from the database for a given object.
     *
     * @param Concrete $object The parent object
     *
     * @return ExtendedBlockContainer The loaded block container
     */
    public function loadBlockData(Concrete $object): ExtendedBlockContainer
    {
        $container = new ExtendedBlockContainer(
            object: $object,
            fieldname: $this->getName(),
            definition: $this,
            lazyLoad: false
        );

        $tableName = $this->getTableName($object->getClassId());
        $db = Db::get();

        try {
            // Validate table name before using it
            IdentifierValidator::validateTableName($tableName);

            // Check if table exists (using parameterized query for table name)
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$tableName]
            );

            if (!$tableExists) {
                return $container;
            }

            // Use quoteIdentifier to safely escape the table name
            $quotedTable = $db->quoteIdentifier($tableName);

            // Load items from database
            $rows = $db->fetchAllAssociative(
                "SELECT * FROM {$quotedTable} WHERE o_id = ? AND fieldname = ? ORDER BY `index` ASC",
                [$object->getId(), $this->getName()]
            );

            foreach ($rows as $row) {
                $item = $this->createBlockItemFromRow($row, $object);
                if ($item) {
                    $container->addItem($item);
                }
            }
        } catch (Exception $e) {
            Logger::error('ExtendedBlock: Error loading block data: ' . $e->getMessage());
        }

        return $container;
    }

    /**
     * Checks if this block definition contains localized fields.
     *
     * @return bool True if localized fields are present
     */
    public function hasLocalizedFields(): bool
    {
        foreach ($this->children as $field) {
            if ($field instanceof Localizedfields) {
                return true;
            }
        }

        return false;
    }

    /**
     * Saves block data to the database.
     *
     * Called when the parent object is saved. This method handles:
     * - Creating/updating the block table schema if needed
     * - Saving all block items to the separate table
     * - Saving localized data to the localized table
     * - Removing deleted items
     *
     * @param Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object The parent object being saved
     * @param array<string, mixed>                                                $params Additional parameters
     */
    public function save(Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object, array $params = []): void
    {
        // ExtendedBlock only supports Concrete objects at root level
        if (!$object instanceof Concrete) {
            return;
        }

        $container = $object->getValueForFieldName($this->getName());
        if (!$container instanceof ExtendedBlockContainer) {
            return;
        }

        $db = Db::get();
        $tableName = $this->getTableName($object->getClassId());

        try {
            // Validate table name before using it
            IdentifierValidator::validateTableName($tableName);

            // Ensure table exists
            $this->ensureTableExists($object->getClassId());

            // Begin transaction for data integrity
            $db->beginTransaction();

            // Use quoteIdentifier to safely escape the table name
            $quotedTable = $db->quoteIdentifier($tableName);

            // Delete existing items for this object/field
            $db->executeStatement(
                "DELETE FROM {$quotedTable} WHERE o_id = ? AND fieldname = ?",
                [$object->getId(), $this->getName()]
            );

            // Insert new items
            $index = 0;
            foreach ($container->getItems() as $item) {
                $this->saveBlockItem($item, $object, $index, $db, $tableName);
                ++$index;
            }

            // Save localized data if present
            if ($this->allowLocalizedFields && $this->hasLocalizedFields()) {
                $this->saveLocalizedData($container, $object);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            Logger::error('ExtendedBlock: Error saving block data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Loads block data from the database for an object.
     *
     * Called when the parent object is loaded. This is the CustomResourcePersistingInterface
     * method that Pimcore calls to load data stored in custom tables.
     *
     * @param Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object The object being loaded
     * @param array<string, mixed>                                                $params Additional parameters
     *
     * @return ExtendedBlockContainer|null The loaded block container or null
     */
    public function load(Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object, array $params = []): ?ExtendedBlockContainer
    {
        // ExtendedBlock only supports Concrete objects at root level
        if (!$object instanceof Concrete) {
            return null;
        }

        return $this->loadBlockData($object);
    }

    /**
     * Pre-processes data when getting from an object.
     *
     * This method handles lazy loading - if the data hasn't been loaded yet
     * and lazy loading is enabled, it loads the data from the database.
     *
     * Required by PreGetDataInterface.
     *
     * @param mixed                $container The container (Concrete object)
     * @param array<string, mixed> $params    Additional parameters
     *
     * @return ExtendedBlockContainer|null The loaded block container
     */
    public function preGetData(mixed $container, array $params = []): mixed
    {
        // ExtendedBlock only supports Concrete objects at root level
        if (!$container instanceof Concrete) {
            return null;
        }

        $data = $container->getObjectVar($this->getName());
        if ($this->getLazyLoading() && !$container->isLazyKeyLoaded($this->getName())) {
            $data = $this->load($container);

            $setter = 'set' . ucfirst($this->getName());
            if (method_exists($container, $setter)) {
                $container->$setter($data);
            }
            $container->markLazyKeyAsLoaded($this->getName());
        }

        return $data;
    }

    /**
     * Deletes all block data for an object.
     *
     * Called when the parent object is deleted.
     *
     * @param Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object The object being deleted
     * @param array<string, mixed>                                                $params Additional parameters
     */
    public function delete(Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object, array $params = []): void
    {
        // ExtendedBlock only supports Concrete objects at root level
        if (!$object instanceof Concrete) {
            return;
        }

        $db = Db::get();
        $tableName = $this->getTableName($object->getClassId());

        try {
            // Validate table name before using it
            IdentifierValidator::validateTableName($tableName);

            // Check if table exists before deleting (parameterized query)
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$tableName]
            );

            if (!$tableExists) {
                return;
            }

            // Use quoteIdentifier to safely escape the table name
            $quotedTable = $db->quoteIdentifier($tableName);

            // Get item IDs for localized data deletion
            $itemIds = $db->fetchFirstColumn(
                "SELECT id FROM {$quotedTable} WHERE o_id = ? AND fieldname = ?",
                [$object->getId(), $this->getName()]
            );

            // Delete localized data first
            if (!empty($itemIds) && $this->hasLocalizedFields()) {
                $localizedTableName = $this->getLocalizedTableName($object->getClassId());
                IdentifierValidator::validateTableName($localizedTableName);

                $localizedTableExists = $db->fetchOne(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                    [$localizedTableName]
                );

                if ($localizedTableExists) {
                    $quotedLocalizedTable = $db->quoteIdentifier($localizedTableName);
                    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                    $db->executeStatement(
                        "DELETE FROM {$quotedLocalizedTable} WHERE ooo_id IN ({$placeholders})",
                        $itemIds
                    );
                }
            }

            // Delete main items
            $db->executeStatement(
                "DELETE FROM {$quotedTable} WHERE o_id = ? AND fieldname = ?",
                [$object->getId(), $this->getName()]
            );
        } catch (Exception $e) {
            Logger::error('ExtendedBlock: Error deleting block data: ' . $e->getMessage());
        }
    }

    /**
     * Returns the main table name for this extended block.
     *
     * @param string $classId The class ID
     *
     * @throws InvalidArgumentException If classId is invalid
     *
     * @return string The validated table name
     */
    public function getTableName(string $classId): string
    {
        // Validate classId before constructing the table name
        IdentifierValidator::validateClassId($classId);

        $tableName = $this->tablePrefix . $classId . '_' . $this->getName();

        // Validate the full table name as well
        IdentifierValidator::validateTableName($tableName);

        return $tableName;
    }

    /**
     * Returns the localized table name for this extended block.
     *
     * @param string $classId The class ID
     *
     * @throws InvalidArgumentException If classId is invalid
     *
     * @return string The validated localized table name
     */
    public function getLocalizedTableName(string $classId): string
    {
        $tableName = $this->getTableName($classId) . '_localized';

        // Validate the full localized table name
        IdentifierValidator::validateTableName($tableName);

        return $tableName;
    }

    /**
     * Validates the field configuration.
     *
     * Checks for:
     * - Valid children field definitions
     * - No nested ExtendedBlock
     * - No Block inside ExtendedBlock
     * - No Fieldcollections inside ExtendedBlock
     * - No Objectbricks inside ExtendedBlock
     * - No LocalizedFields inside ExtendedBlock
     * - No Classificationstore inside ExtendedBlock
     * - No AdvancedManyToManyRelation inside ExtendedBlock
     * - No AdvancedManyToManyObjectRelation inside ExtendedBlock
     * - No ReverseObjectRelation inside ExtendedBlock
     *
     * @throws Exception If validation fails
     */
    public function validate(): void
    {
        // Check for nested ExtendedBlock in LocalizedFields
        if ($this->disallowAddingInLocalizedField) {
            throw new Exception('ExtendedBlock with localized fields cannot be added inside a LocalizedFields container. This would create an infinite recursion. Please restructure your class definition.');
        }

        // Validate children field definitions
        foreach ($this->children as $field) {
            // Check for nested ExtendedBlock
            if ($field instanceof self) {
                throw new Exception('ExtendedBlock cannot contain another ExtendedBlock.');
            }

            // Check for Block inside ExtendedBlock
            if ($field instanceof Block) {
                throw new Exception('ExtendedBlock cannot contain a Block. Block nesting is not supported to ensure data integrity and prevent performance issues.');
            }

            // Check for Fieldcollections inside ExtendedBlock
            if ($field instanceof Fieldcollections) {
                throw new Exception('ExtendedBlock cannot contain Fieldcollections. Fieldcollections are complex container types that cannot be nested inside ExtendedBlock.');
            }

            // Check for Objectbricks inside ExtendedBlock
            if ($field instanceof Objectbricks) {
                throw new Exception('ExtendedBlock cannot contain Objectbricks. Objectbricks are complex container types that cannot be nested inside ExtendedBlock.');
            }

            // Check for LocalizedFields inside ExtendedBlock
            if ($field instanceof Localizedfields) {
                throw new Exception('ExtendedBlock cannot contain LocalizedFields. Localized fields are not supported inside ExtendedBlock.');
            }

            // Check for Classificationstore inside ExtendedBlock
            if ($field instanceof Classificationstore) {
                throw new Exception('ExtendedBlock cannot contain Classificationstore. Classificationstore stores data in complex structures incompatible with ExtendedBlock\'s separate table storage.');
            }

            // UNSAFE RELATIONAL TYPES - Must be explicitly blocked
            // These types use complex metadata storage or virtual relation lookups
            // that are incompatible with ExtendedBlock's separate table storage.

            // Check for AdvancedManyToManyRelation (has metadata per relation)
            if ($field instanceof AdvancedManyToManyRelation) {
                throw new Exception('ExtendedBlock cannot contain AdvancedManyToManyRelation. This field type stores additional metadata per relation in separate tables, which is incompatible with ExtendedBlock\'s storage model. Use ManyToManyRelation instead for simple relations.');
            }

            // Check for AdvancedManyToManyObjectRelation (has metadata per relation)
            if ($field instanceof AdvancedManyToManyObjectRelation) {
                throw new Exception('ExtendedBlock cannot contain AdvancedManyToManyObjectRelation. This field type stores additional metadata per relation in separate tables, which is incompatible with ExtendedBlock\'s storage model. Use ManyToManyObjectRelation instead for simple relations.');
            }

            // Check for ReverseObjectRelation (virtual field reading owner's relations)
            if ($field instanceof ReverseObjectRelation) {
                throw new Exception('ExtendedBlock cannot contain ReverseObjectRelation. This field type is a virtual/computed field that reads inverse relations from another object\'s forward relation, which cannot be stored independently. Consider using a different approach for bi-directional relation tracking.');
            }
        }
    }

    /**
     * Checks if this data type should be disallowed in LocalizedFields.
     *
     * Returns true if this ExtendedBlock contains localized fields,
     * preventing infinite recursion.
     *
     * @return bool True if should be disallowed
     */
    public function isDisallowedInLocalizedFields(): bool
    {
        return $this->hasLocalizedFields();
    }

    /**
     * {@inheritdoc}
     *
     * Enriches the layout definition for the admin UI.
     */
    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        return $this;
    }

    /**
     * Returns the data for grid view.
     *
     * Returns a structured array with data for table rendering in ExtJS grid.
     * Format follows structuredTable pattern for proper table UI rendering:
     * ```
     * [
     *     'count' => int,              // Total number of items
     *     'fields' => [                // Column definitions (child fields)
     *         ['key' => string, 'label' => string],
     *         ...
     *     ],
     *     'items' => [                 // Row data (max GRID_MAX_PREVIEW_ITEMS)
     *         ['fieldKey' => value, ...],  // Values keyed by field name
     *         ...
     *     ]
     * ]
     * ```
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return array<string, mixed> The grid display data
     */
    public function getDataForGrid(mixed $data, ?Concrete $object = null, array $params = []): array
    {
        $result = [
            'count' => 0,
            'fields' => [],
            'items' => [],
        ];

        if (!$data instanceof ExtendedBlockContainer) {
            return $result;
        }

        $items = $data->getItems();
        $result['count'] = count($items);

        // Get field definitions for column headers (key + label)
        $fieldDefinitions = $this->getGridDisplayableFieldDefinitions();
        $result['fields'] = $fieldDefinitions;

        // Generate row data for limited items to keep grid lightweight
        $maxItems = min(self::GRID_MAX_PREVIEW_ITEMS, count($items));
        for ($i = 0; $i < $maxItems; ++$i) {
            $item = $items[$i];
            $rowData = [];
            foreach ($fieldDefinitions as $fieldDef) {
                $value = $item->getFieldValue($fieldDef['key']);
                $rowData[$fieldDef['key']] = $this->formatValueForGridPreview($value);
            }
            $result['items'][] = $rowData;
        }

        return $result;
    }

    /**
     * Returns the data for CSV export.
     *
     * Exports block data in a format suitable for CSV files.
     * Each block item is represented as a JSON-encoded summary
     * with field values, separated by newlines.
     *
     * @param Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object The parent object
     * @param array<string, mixed>                                                $params Additional parameters
     *
     * @return string The CSV export string
     */
    public function getForCsvExport(
        Localizedfield|FieldcollectionAbstract|ObjectbrickAbstract|Concrete $object,
        array $params = [],
    ): string {
        $data = $this->getDataFromObjectParam($object, $params);

        if (!$data instanceof ExtendedBlockContainer) {
            return '';
        }

        $items = $data->getItems();
        if (empty($items)) {
            return '';
        }

        $lines = [];
        foreach ($items as $index => $item) {
            $itemValues = [];
            foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
                if (!$fieldDef instanceof Localizedfields) {
                    $value = $item->getFieldValue($fieldName);
                    $itemValues[$fieldName] = $this->formatValueForCsvExport($value);
                }
            }
            $lines[] = sprintf(
                '[%d] %s',
                $index,
                implode(' | ', array_map(
                    static fn (string $k, string $v): string => "$k: $v",
                    array_keys($itemValues),
                    array_values($itemValues),
                )),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Returns the data for editmode in admin.
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return array<int, array<string, mixed>>|null The editmode data
     */
    public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?array
    {
        if (!$data instanceof ExtendedBlockContainer) {
            return null;
        }

        $result = [];
        foreach ($data->getItems() as $item) {
            $itemData = [
                'id' => $item->getId(),
                'type' => $item->getType(),
                'index' => $item->getIndex(),
                'data' => [],
                'localizedData' => [],
            ];

            // Get field data from children definitions
            foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
                if (!$fieldDef instanceof Localizedfields) {
                    $value = $item->getFieldValue($fieldName);
                    // Use method_exists to safely call getDataForEditmode
                    if (method_exists($fieldDef, 'getDataForEditmode')) {
                        $itemData['data'][$fieldName] = $fieldDef->getDataForEditmode($value, $object);
                    } else {
                        $itemData['data'][$fieldName] = $value;
                    }
                }
            }

            $result[] = $itemData;
        }

        return $result;
    }

    /**
     * Returns the data from editmode submission.
     *
     * @param mixed                $data   The submitted data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return ExtendedBlockContainer The block container
     */
    public function getDataFromEditmode(mixed $data, ?Concrete $object = null, array $params = []): ExtendedBlockContainer
    {
        $container = new ExtendedBlockContainer(
            object: $object,
            fieldname: $this->getName(),
            definition: $this,
            lazyLoad: false
        );

        if (!is_array($data)) {
            return $container;
        }

        foreach ($data as $index => $itemData) {
            $type = $itemData['type'] ?? 'default';

            $item = new ExtendedBlockItem(
                type: $type,
                index: $index,
                object: $object,
                fieldname: $this->getName()
            );

            if (isset($itemData['id'])) {
                $item->setId((int) $itemData['id']);
            }

            // Process field data from children definitions
            foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
                if (!$fieldDef instanceof Localizedfields) {
                    if (isset($itemData['data'][$fieldName])) {
                        // Use method_exists to safely call getDataFromEditmode
                        if (method_exists($fieldDef, 'getDataFromEditmode')) {
                            $value = $fieldDef->getDataFromEditmode($itemData['data'][$fieldName], $object);
                            $item->setFieldValue($fieldName, $value);
                        } else {
                            $item->setFieldValue($fieldName, $itemData['data'][$fieldName]);
                        }
                    }
                }
            }

            $container->addItem($item);
        }

        return $container;
    }

    /**
     * Returns data for version preview.
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return string|null The HTML preview string
     */
    public function getDataForVersionPreview(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        if ($data instanceof ExtendedBlockContainer) {
            $count = count($data->getItems());

            return sprintf('<span>%d extended block item%s</span>', $count, 1 !== $count ? 's' : '');
        }

        return '<span>No items</span>';
    }

    /**
     * Compares two values for equality.
     *
     * @param mixed $oldValue The old value
     * @param mixed $newValue The new value
     *
     * @return bool True if values are equal
     */
    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        if (!$oldValue instanceof ExtendedBlockContainer || !$newValue instanceof ExtendedBlockContainer) {
            return $oldValue === $newValue;
        }

        $oldItems = $oldValue->getItems();
        $newItems = $newValue->getItems();

        if (count($oldItems) !== count($newItems)) {
            return false;
        }

        foreach ($oldItems as $index => $oldItem) {
            if (!isset($newItems[$index])) {
                return false;
            }

            $newItem = $newItems[$index];
            if ($oldItem->getType() !== $newItem->getType()) {
                return false;
            }

            // Compare field values from children definitions
            foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
                $oldFieldValue = $oldItem->getFieldValue($fieldName);
                $newFieldValue = $newItem->getFieldValue($fieldName);

                // Use method_exists to safely call isEqual
                if (method_exists($fieldDef, 'isEqual')) {
                    if (!$fieldDef->isEqual($oldFieldValue, $newFieldValue)) {
                        return false;
                    }
                } elseif ($oldFieldValue !== $newFieldValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the parameter data for the object getter.
     *
     * @return string|null The getter parameter type
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\' . ExtendedBlockContainer::class;
    }

    /**
     * Returns the return type declaration for getter.
     *
     * @return string|null The return type
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\' . ExtendedBlockContainer::class;
    }

    /**
     * Returns the PHP type declaration.
     *
     * @return string|null The type declaration
     */
    public function getPhpType(): ?string
    {
        return '\\' . ExtendedBlockContainer::class . '|null';
    }

    /**
     * Exports data for VarExporter.
     *
     * @param mixed                $value  The value to export
     * @param Concrete             $object The object
     * @param array<string, mixed> $params Parameters
     *
     * @return array<int, array<string, mixed>>|null The exported data
     */
    public function getVarExporterData(mixed $value, Concrete $object, array $params = []): ?array
    {
        if (!$value instanceof ExtendedBlockContainer) {
            return null;
        }

        return $this->getDataForEditmode($value, $object, $params);
    }

    /**
     * Imports data from VarExporter format.
     *
     * @param mixed                $data   The data to import
     * @param Concrete             $object The object
     * @param array<string, mixed> $params Parameters
     *
     * @return ExtendedBlockContainer The imported data
     */
    public function setVarExporterData(mixed $data, Concrete $object, array $params = []): ExtendedBlockContainer
    {
        return $this->getDataFromEditmode($data, $object, $params);
    }

    // Getters and Setters

    /**
     * @return array<Layout|Data>
     */
    public function getLayoutDefinitions(): array
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param array<Layout|Data> $layoutDefinitions
     */
    public function setLayoutDefinitions(array $layoutDefinitions): static
    {
        $this->layoutDefinitions = $layoutDefinitions;

        return $this;
    }

    /**
     * @return array<string, array<string, mixed>>
     *
     * @deprecated Use getChildren() instead. Block definitions are no longer used.
     */
    public function getBlockDefinitions(): array
    {
        return $this->blockDefinitions;
    }

    /**
     * @param array<string, array<string, mixed>> $blockDefinitions
     *
     * @deprecated Use setChildren() instead. Block definitions are no longer used.
     */
    public function setBlockDefinitions(array $blockDefinitions): static
    {
        $this->blockDefinitions = $blockDefinitions;

        return $this;
    }

    public function isAllowLocalizedFields(): bool
    {
        return $this->allowLocalizedFields;
    }

    public function setAllowLocalizedFields(bool $allowLocalizedFields): static
    {
        $this->allowLocalizedFields = $allowLocalizedFields;

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    public function setMaxItems(?int $maxItems): static
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getMinItems(): int
    {
        return $this->minItems;
    }

    public function setMinItems(int $minItems): static
    {
        $this->minItems = $minItems;

        return $this;
    }

    public function isCollapsible(): bool
    {
        return $this->collapsible;
    }

    public function setCollapsible(bool $collapsible): static
    {
        $this->collapsible = $collapsible;

        return $this;
    }

    public function isCollapsed(): bool
    {
        return $this->collapsed;
    }

    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        return $this;
    }

    public function isLazyLoading(): bool
    {
        return $this->lazyLoading;
    }

    /**
     * Returns whether lazy loading is enabled.
     *
     * Required by LazyLoadingSupportInterface.
     *
     * @return bool True if lazy loading is enabled
     */
    public function getLazyLoading(): bool
    {
        return $this->lazyLoading;
    }

    public function setLazyLoading(bool $lazyLoading): static
    {
        $this->lazyLoading = $lazyLoading;

        return $this;
    }

    public function isDisallowAddRemove(): bool
    {
        return $this->disallowAddRemove;
    }

    public function setDisallowAddRemove(bool $disallowAddRemove): static
    {
        $this->disallowAddRemove = $disallowAddRemove;

        return $this;
    }

    public function isDisallowReorder(): bool
    {
        return $this->disallowReorder;
    }

    public function setDisallowReorder(bool $disallowReorder): static
    {
        $this->disallowReorder = $disallowReorder;

        return $this;
    }

    public function getStyleElement(): ?string
    {
        return $this->styleElement;
    }

    public function setStyleElement(?string $styleElement): static
    {
        $this->styleElement = $styleElement;

        return $this;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix(string $tablePrefix): static
    {
        $this->tablePrefix = $tablePrefix;

        return $this;
    }

    /**
     * Returns the child field definitions.
     *
     * @return array<Data|Layout>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Sets the child field definitions.
     *
     * @param array<Data|Layout> $children
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        $this->fieldDefinitionsCache = null;

        return $this;
    }

    /**
     * Checks if there are child field definitions.
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }

    /**
     * Adds a child field definition.
     */
    public function addChild(Data|Layout $child): void
    {
        $this->children[] = $child;
        $this->fieldDefinitionsCache = null;
    }

    /**
     * Returns field definitions from children.
     *
     * @return array<string, Data>
     */
    public function getFieldDefinitions(): array
    {
        if (null !== $this->fieldDefinitionsCache) {
            return $this->fieldDefinitionsCache;
        }

        $definitions = [];
        foreach ($this->children as $child) {
            if ($child instanceof Data) {
                $definitions[$child->getName()] = $child;
            }
        }

        $this->fieldDefinitionsCache = $definitions;

        return $definitions;
    }

    /**
     * Returns a specific field definition by name.
     */
    public function getFieldDefinition(string $name): ?Data
    {
        $definitions = $this->getFieldDefinitions();

        return $definitions[$name] ?? null;
    }

    /**
     * Sets field definitions (resets cache).
     *
     * @param array<string, Data>|null $definitions
     */
    public function setFieldDefinitions(?array $definitions): void
    {
        $this->fieldDefinitionsCache = $definitions;
    }

    /**
     * Returns variables that should be excluded from export.
     *
     * These variables are runtime caches or computed values that
     * don't need to be persisted and should be rebuilt on load.
     *
     * @return array<string> List of blocked variable names
     */
    public function getBlockedVarsForExport(): array
    {
        return [
            'fieldDefinitionsCache',
            'blockedVarsForExport',
        ];
    }

    /**
     * Creates a block item from a database row.
     *
     * @param array<string, mixed> $row    The database row
     * @param Concrete             $object The parent object
     *
     * @return ExtendedBlockItem|null The created block item
     */
    protected function createBlockItemFromRow(array $row, Concrete $object): ?ExtendedBlockItem
    {
        $type = $row['type'] ?? 'default';
        $index = (int) ($row['index'] ?? 0);

        $item = new ExtendedBlockItem(
            type: $type,
            index: $index,
            object: $object,
            fieldname: $this->getName()
        );

        $item->setId((int) $row['id']);

        // Map row data to item fields based on children field definitions
        foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            // These fields store data as multiple columns (e.g., fieldname__id, fieldname__type)
            if ($fieldDef instanceof QueryResourcePersistenceAwareInterface && method_exists($fieldDef, 'getQueryColumnType')) {
                $queryColumnTypes = $fieldDef->getQueryColumnType();
                if (is_array($queryColumnTypes)) {
                    // Build the query resource data array from the row
                    $queryData = [];
                    $hasData = false;
                    foreach (array_keys($queryColumnTypes) as $colSuffix) {
                        $colName = $fieldName . '__' . $colSuffix;
                        if (isset($row[$colName])) {
                            $queryData[$fieldName . '__' . $colSuffix] = $row[$colName];
                            if (null !== $row[$colName]) {
                                $hasData = true;
                            }
                        }
                    }

                    // Only load the element if we have valid data
                    if ($hasData && isset($queryData[$fieldName . '__id']) && isset($queryData[$fieldName . '__type'])) {
                        $element = Element\Service::getElementById(
                            $queryData[$fieldName . '__type'],
                            (int) $queryData[$fieldName . '__id']
                        );
                        $item->setFieldValue($fieldName, $element);
                    }
                    continue;
                }
            }

            // Handle simple fields
            if (isset($row[$fieldName])) {
                if (method_exists($fieldDef, 'getDataFromResource')) {
                    $value = $fieldDef->getDataFromResource($row[$fieldName], $object);
                } else {
                    $value = $row[$fieldName];
                }
                $item->setFieldValue($fieldName, $value);
            }
        }

        return $item;
    }

    /**
     * Loads localized data for block items.
     *
     * @param ExtendedBlockContainer $container The container to populate
     * @param Concrete               $object    The parent object
     */
    protected function loadLocalizedData(ExtendedBlockContainer $container, Concrete $object): void
    {
        $localizedTableName = $this->getLocalizedTableName($object->getClassId());
        $db = Db::get();

        try {
            // Validate table name before using it
            IdentifierValidator::validateTableName($localizedTableName);

            // Check if localized table exists (using parameterized query for table name)
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$localizedTableName]
            );

            if (!$tableExists) {
                return;
            }

            // Load all localized data at once for efficiency
            $itemIds = array_map(static fn ($item) => $item->getId(), $container->getItems());
            if (empty($itemIds)) {
                return;
            }

            // Use quoteIdentifier to safely escape the table name
            $quotedTable = $db->quoteIdentifier($localizedTableName);

            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $localizedRows = $db->fetchAllAssociative(
                "SELECT * FROM {$quotedTable} WHERE ooo_id IN ({$placeholders})",
                $itemIds
            );

            // Group by item ID and language
            $localizedData = [];
            foreach ($localizedRows as $row) {
                $itemId = (int) $row['ooo_id'];
                $language = $row['language'];
                $localizedData[$itemId][$language] = $row;
            }

            // Apply localized data to items
            foreach ($container->getItems() as $item) {
                $itemId = $item->getId();
                if (isset($localizedData[$itemId])) {
                    $item->setLocalizedData($localizedData[$itemId]);
                }
            }
        } catch (Exception $e) {
            Logger::error('ExtendedBlock: Error loading localized data: ' . $e->getMessage());
        }
    }

    /**
     * Saves a single block item to the database.
     *
     * @param ExtendedBlockItem         $item      The item to save
     * @param Concrete                  $object    The parent object
     * @param int                       $index     The item index/position
     * @param \Doctrine\DBAL\Connection $db        The database connection
     * @param string                    $tableName The target table name (already validated)
     */
    protected function saveBlockItem(
        ExtendedBlockItem $item,
        Concrete $object,
        int $index,
        \Doctrine\DBAL\Connection $db,
        string $tableName,
    ): void {
        // Build data array for the insert.
        // Note: We use raw SQL with quoteIdentifier() for column names because 'index' is a MySQL
        // reserved keyword. DBAL's insert() method does NOT automatically quote reserved keywords.
        $data = [
            'o_id' => $object->getId(),
            'fieldname' => $this->getName(),
            'index' => $index,
            'type' => $item->getType(),
        ];

        // Add field values based on children field definitions
        foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
            if ($fieldDef instanceof Localizedfields) {
                continue;
            }

            $value = $item->getFieldValue($fieldName);

            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            // These fields store data as multiple columns (e.g., fieldname__id, fieldname__type)
            if ($fieldDef instanceof QueryResourcePersistenceAwareInterface && method_exists($fieldDef, 'getDataForQueryResource')) {
                $queryData = $fieldDef->getDataForQueryResource($value, $object);
                if (is_array($queryData)) {
                    foreach ($queryData as $colKey => $colValue) {
                        // The key returned is like "fieldname__id" - use it directly
                        $data[$colKey] = $colValue;
                    }
                    continue;
                }
            }

            // Use the concrete field type's method if available for simple fields
            if (method_exists($fieldDef, 'getDataForResource')) {
                $data[$fieldName] = $fieldDef->getDataForResource($value, $object);
            } else {
                $data[$fieldName] = $value;
            }
        }

        // Build SQL manually with quoted identifiers to handle MySQL reserved keywords like 'index'.
        // DBAL's insert() method does NOT automatically quote reserved keywords in column names.
        $quotedTable = $db->quoteIdentifier($tableName);
        $quotedColumns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $column => $value) {
            $quotedColumns[] = $db->quoteIdentifier($column);
            $placeholders[] = '?';
            $values[] = $value;
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $quotedTable,
            implode(', ', $quotedColumns),
            implode(', ', $placeholders)
        );

        $db->executeStatement($sql, $values);
        $item->setId((int) $db->lastInsertId());
    }

    /**
     * Saves localized data for all block items.
     *
     * @param ExtendedBlockContainer $container The container with items
     * @param Concrete               $object    The parent object
     */
    protected function saveLocalizedData(ExtendedBlockContainer $container, Concrete $object): void
    {
        $localizedTableName = $this->getLocalizedTableName($object->getClassId());
        $db = Db::get();

        // Validate table name before using it
        IdentifierValidator::validateTableName($localizedTableName);

        // Ensure localized table exists
        $this->ensureLocalizedTableExists($object->getClassId());

        // Use quoteIdentifier to safely escape the table name
        $quotedTable = $db->quoteIdentifier($localizedTableName);

        // Delete existing localized data
        $itemIds = array_map(static fn ($item) => $item->getId(), $container->getItems());
        if (!empty($itemIds)) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $db->executeStatement(
                "DELETE FROM {$quotedTable} WHERE ooo_id IN ({$placeholders})",
                $itemIds
            );
        }

        // Get available languages from Pimcore configuration
        $languages = \Pimcore\Tool::getValidLanguages();

        // Insert localized data for each item and language
        foreach ($container->getItems() as $item) {
            $localizedData = $item->getLocalizedData();
            if (empty($localizedData)) {
                continue;
            }

            foreach ($languages as $language) {
                if (!isset($localizedData[$language])) {
                    continue;
                }

                // Build data array for the insert.
                $data = [
                    'ooo_id' => $item->getId(),
                    'language' => $language,
                ];

                // LocalizedFields are no longer supported in ExtendedBlock.
                // This code path is maintained for backward compatibility with
                // existing installations that may have localized data.
                Logger::debug('ExtendedBlock: saveLocalizedData called - this is deprecated functionality');

                // Build SQL manually with quoted identifiers for consistency.
                $quotedColumns = [];
                $placeholders = [];
                $values = [];

                foreach ($data as $column => $value) {
                    $quotedColumns[] = $db->quoteIdentifier($column);
                    $placeholders[] = '?';
                    $values[] = $value;
                }

                $sql = sprintf(
                    'INSERT INTO %s (%s) VALUES (%s)',
                    $quotedTable,
                    implode(', ', $quotedColumns),
                    implode(', ', $placeholders)
                );

                $db->executeStatement($sql, $values);
            }
        }
    }

    /**
     * Ensures the main block table exists.
     *
     * Creates the table if it doesn't exist, with all required columns
     * based on the block definitions.
     *
     * @param string $classId The class ID
     */
    protected function ensureTableExists(string $classId): void
    {
        // getTableName() validates classId and tableName
        $tableName = $this->getTableName($classId);
        $db = Db::get();

        // Check if table already exists (parameterized query)
        $tableExists = $db->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        if ($tableExists) {
            // Table exists - validate and sync schema
            $this->validateAndSyncTableSchema($tableName, $db);

            return;
        }

        // Use quoteIdentifier to safely escape the table name
        $quotedTable = $db->quoteIdentifier($tableName);

        // Build CREATE TABLE statement
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`o_id` INT(11) UNSIGNED NOT NULL',
            '`fieldname` VARCHAR(70) NOT NULL',
            '`index` INT(11) UNSIGNED NOT NULL DEFAULT 0',
            '`type` VARCHAR(100) NOT NULL DEFAULT "default"',
        ];

        // Add columns for each field in children definitions
        foreach ($this->getFieldDefinitions() as $fieldDef) {
            if ($fieldDef instanceof Localizedfields) {
                continue;
            }

            $fieldName = $fieldDef->getName();

            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            if ($fieldDef instanceof QueryResourcePersistenceAwareInterface && method_exists($fieldDef, 'getQueryColumnType')) {
                $queryColumnTypes = $fieldDef->getQueryColumnType();
                if (is_array($queryColumnTypes)) {
                    foreach ($queryColumnTypes as $colSuffix => $colType) {
                        $colName = $fieldName . '__' . $colSuffix;
                        IdentifierValidator::validateColumnName($colName);
                        $quotedColumn = $db->quoteIdentifier($colName);
                        $columns[] = "{$quotedColumn} {$colType}";
                    }
                    continue;
                }
            }

            // Handle simple fields with getColumnType
            if (method_exists($fieldDef, 'getColumnType')) {
                $columnType = $fieldDef->getColumnType();
                if ($columnType) {
                    IdentifierValidator::validateColumnName($fieldName);
                    $quotedColumn = $db->quoteIdentifier($fieldName);
                    $columns[] = "{$quotedColumn} {$columnType}";
                }
            }
        }

        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `o_id` (`o_id`)';
        $columns[] = 'INDEX `fieldname` (`fieldname`)';
        $columns[] = 'INDEX `type` (`type`)';

        $sql = "CREATE TABLE {$quotedTable} (\n" . implode(",\n", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->executeStatement($sql);
    }

    /**
     * Validates and synchronizes table schema with field definitions.
     *
     * Adds missing columns to the table when field definitions have been added.
     * This prevents "Unknown column" SQL errors by ensuring the database schema
     * matches the current class definition.
     *
     * Note: Columns are only added, not removed, to prevent data loss.
     * Unused columns from removed fields should be cleaned up manually.
     *
     * @param string                    $tableName The table name
     * @param \Doctrine\DBAL\Connection $db        The database connection
     */
    protected function validateAndSyncTableSchema(string $tableName, \Doctrine\DBAL\Connection $db): void
    {
        // Get existing columns from the database
        $existingColumns = [];
        $rows = $db->fetchAllAssociative(
            'SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
        foreach ($rows as $row) {
            $existingColumns[$row['COLUMN_NAME']] = true;
        }

        // Check if all required field columns exist
        $quotedTable = $db->quoteIdentifier($tableName);
        foreach ($this->getFieldDefinitions() as $fieldDef) {
            if ($fieldDef instanceof Localizedfields) {
                continue;
            }

            $fieldName = $fieldDef->getName();

            // Handle relation fields that use QueryResourcePersistenceAwareInterface
            if ($fieldDef instanceof QueryResourcePersistenceAwareInterface && method_exists($fieldDef, 'getQueryColumnType')) {
                $queryColumnTypes = $fieldDef->getQueryColumnType();
                if (is_array($queryColumnTypes)) {
                    foreach ($queryColumnTypes as $colSuffix => $colType) {
                        $colName = $fieldName . '__' . $colSuffix;
                        if (!isset($existingColumns[$colName])) {
                            IdentifierValidator::validateColumnName($colName);
                            $quotedColumn = $db->quoteIdentifier($colName);

                            try {
                                $sql = "ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$colType}";
                                $db->executeStatement($sql);
                                Logger::info("ExtendedBlock: Added missing column {$colName} to {$tableName}");
                            } catch (Exception $e) {
                                Logger::error("ExtendedBlock: Failed to add column {$colName} to {$tableName}: " . $e->getMessage());
                                throw new RuntimeException("Failed to synchronize ExtendedBlock table schema for field '{$colName}'. The table '{$tableName}' may need manual migration. Error: " . $e->getMessage(), 0, $e);
                            }
                        }
                    }
                    continue;
                }
            }

            // Handle simple fields with getColumnType
            if (method_exists($fieldDef, 'getColumnType')) {
                $columnType = $fieldDef->getColumnType();
                if ($columnType) {
                    // If column doesn't exist, add it
                    if (!isset($existingColumns[$fieldName])) {
                        IdentifierValidator::validateColumnName($fieldName);
                        $quotedColumn = $db->quoteIdentifier($fieldName);

                        try {
                            $sql = "ALTER TABLE {$quotedTable} ADD COLUMN {$quotedColumn} {$columnType}";
                            $db->executeStatement($sql);
                            Logger::info("ExtendedBlock: Added missing column {$fieldName} to {$tableName}");
                        } catch (Exception $e) {
                            Logger::error("ExtendedBlock: Failed to add column {$fieldName} to {$tableName}: " . $e->getMessage());
                            throw new RuntimeException("Failed to synchronize ExtendedBlock table schema for field '{$fieldName}'. The table '{$tableName}' may need manual migration. Error: " . $e->getMessage(), 0, $e);
                        }
                    }
                }
            }
        }
    }

    /**
     * Ensures the localized block table exists.
     *
     * Creates the localized table if it doesn't exist, with columns
     * for localized field definitions.
     *
     * @param string $classId The class ID
     */
    protected function ensureLocalizedTableExists(string $classId): void
    {
        // getLocalizedTableName() validates via getTableName()
        $tableName = $this->getLocalizedTableName($classId);
        $db = Db::get();

        // Check if table already exists (parameterized query)
        $tableExists = $db->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        if ($tableExists) {
            return;
        }

        // Use quoteIdentifier to safely escape the table name
        $quotedTable = $db->quoteIdentifier($tableName);

        // Build CREATE TABLE statement
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`ooo_id` INT(11) UNSIGNED NOT NULL',
            '`language` VARCHAR(10) NOT NULL',
        ];

        // Note: LocalizedFields not allowed in ExtendedBlock, so this table
        // will have only base columns. Kept for backward compatibility.

        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `ooo_id` (`ooo_id`)';
        $columns[] = 'INDEX `language` (`language`)';
        $columns[] = 'UNIQUE KEY `ooo_id_language` (`ooo_id`, `language`)';

        $sql = "CREATE TABLE {$quotedTable} (\n" . implode(",\n", $columns) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->executeStatement($sql);
    }

    /**
     * Gets field definitions suitable for grid display.
     *
     * Returns an array of field definitions with key and label for column headers.
     * Filters to simple field types that can be displayed as text.
     *
     * Note: If a field's title is not defined, the field name is used as the
     * label fallback. For best results, ensure all child fields have titles set.
     *
     * @return array<int, array{key: string, label: string}> Array of field definitions
     */
    private function getGridDisplayableFieldDefinitions(): array
    {
        $displayableTypes = [
            'input',
            'textarea',
            'wysiwyg',
            'numeric',
            'checkbox',
            'date',
            'datetime',
            'select',
            'multiselect',
            'country',
            'countrymultiselect',
            'language',
            'languagemultiselect',
            'email',
            'gender',
            'slider',
            'booleanSelect',
            // Relation types - display as path/key instead of IDs
            'manyToOneRelation',
            'manyToManyRelation',
            'manyToManyObjectRelation',
            // Media types - display as full asset path
            'image',
            'link',
        ];

        $fields = [];

        foreach ($this->getFieldDefinitions() as $fieldName => $fieldDef) {
            if (in_array($fieldDef->getFieldtype(), $displayableTypes, true)) {
                $fields[] = [
                    'key' => $fieldName,
                    'label' => $fieldDef->getTitle() ?: $fieldName,
                ];
            }
        }

        return $fields;
    }

    /**
     * Formats a value for grid preview display.
     *
     * Converts various value types to a short string suitable for grid display.
     * For media types (Image, Link), returns the full asset path for human-readable display.
     *
     * @param mixed $value The value to format
     *
     * @return string The formatted string
     */
    private function formatValueForGridPreview(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        // Handle Asset objects (Image field) - show full asset path
        if ($value instanceof Asset) {
            return $value->getRealFullPath();
        }

        // Handle Link data objects - show the resolved path or direct URL
        if ($value instanceof Link) {
            return $this->formatLinkForGridPreview($value);
        }

        // Handle relation elements (ManyToOneRelation) - show path/key instead of ID
        if ($value instanceof Element\ElementInterface) {
            // Try to get the key/name for display
            $key = method_exists($value, 'getKey') ? $value->getKey() : null;
            if ($key) {
                return $key;
            }

            // Fallback to path
            return $value->getRealFullPath();
        }

        // Handle arrays (ManyToManyRelation) - convert element objects to keys
        if (is_array($value)) {
            $formattedValues = [];
            foreach ($value as $item) {
                if ($item instanceof Asset) {
                    $formattedValues[] = $item->getRealFullPath();
                } elseif ($item instanceof Element\ElementInterface) {
                    $key = method_exists($item, 'getKey') ? $item->getKey() : null;
                    $formattedValues[] = $key ?: $item->getRealFullPath();
                } else {
                    $formattedValues[] = (string) $item;
                }
            }

            return implode(', ', array_filter($formattedValues));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $stringValue = (string) $value;

        // Truncate long strings
        if (strlen($stringValue) > self::GRID_MAX_STRING_LENGTH) {
            return substr($stringValue, 0, self::GRID_TRUNCATE_LENGTH) . '...';
        }

        // Strip HTML tags for WYSIWYG content
        return strip_tags($stringValue);
    }

    /**
     * Formats a Link value for grid preview display.
     *
     * Returns the path of the linked element or the direct URL.
     *
     * @param Link $link The link value to format
     *
     * @return string The formatted path or URL
     */
    private function formatLinkForGridPreview(Link $link): string
    {
        // Get the resolved path from the link
        $path = $link->getPath();

        if (!empty($path)) {
            return $path;
        }

        // Fallback to text or empty string
        $text = $link->getText();
        if (!empty($text)) {
            return $text;
        }

        return '';
    }

    /**
     * Formats a value for CSV export.
     *
     * Converts various value types to a string suitable for CSV export.
     * For media types (Image, Link), exports the full asset path.
     *
     * @param mixed $value The value to format
     *
     * @return string The formatted string
     */
    private function formatValueForCsvExport(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Handle Asset objects (Image field) - export full path
        if ($value instanceof Asset) {
            return $value->getRealFullPath();
        }

        // Handle Link data objects - export resolved path or URL
        if ($value instanceof Link) {
            return $this->formatLinkForGridPreview($value);
        }

        // Handle Element interfaces (relations)
        if ($value instanceof Element\ElementInterface) {
            return $value->getRealFullPath();
        }

        if (is_array($value)) {
            $formattedValues = [];
            foreach ($value as $item) {
                if ($item instanceof Asset) {
                    $formattedValues[] = $item->getRealFullPath();
                } elseif ($item instanceof Element\ElementInterface) {
                    $formattedValues[] = $item->getRealFullPath();
                } else {
                    $formattedValues[] = (string) $item;
                }
            }

            return implode(',', array_filter($formattedValues));
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        // Strip HTML tags and normalize whitespace
        $stringValue = strip_tags((string) $value);
        $stringValue = preg_replace('/\s+/', ' ', $stringValue) ?? $stringValue;

        return trim($stringValue);
    }
}
