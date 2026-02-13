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

use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockContainer;
use ExtendedBlockBundle\Model\DataObject\Data\ExtendedBlockItem;
use Pimcore\Db;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Concrete;

/**
 * Extended Block Data Type Definition.
 *
 * This class defines the ExtendedBlock data type for Pimcore class definitions.
 * Unlike the standard Block type that stores data as serialized JSON in a single column,
 * ExtendedBlock stores each block item in a separate database table row, providing:
 *
 * - Better query performance and indexability
 * - Proper relational data model
 * - Support for localized fields within block items
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
class ExtendedBlock extends Data implements Data\QueryResourcePersistenceAwareInterface, Data\LayoutDefinitionEnrichmentInterface, Data\VarExporterInterface
{
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
     * Whether to allow localized fields within block items.
     *
     * When enabled, block items can contain LocalizedFields, and separate
     * localized tables will be created to store translations.
     */
    public bool $allowLocalizedFields = true;

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
     * Whether this field is disallowed in localized fields context.
     *
     * ExtendedBlock with localized fields cannot be nested inside
     * another LocalizedFields container to prevent infinite recursion.
     */
    public bool $disallowAddingInLocalizedField = false;

    /**
     * Database table prefix for this extended block.
     */
    protected string $tablePrefix = 'object_eb_';

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
        return '\\'.ExtendedBlockContainer::class.'|null';
    }

    /**
     * Returns the PHPDoc type hint for return values.
     *
     * @return string|null The return type
     */
    public function getPhpdocReturnType(): ?string
    {
        return '\\'.ExtendedBlockContainer::class.'|null';
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
     * @return array|string The query column type
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
            // Check if table exists
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$tableName]
            );

            if (!$tableExists) {
                return $container;
            }

            // Load items from database
            $rows = $db->fetchAllAssociative(
                "SELECT * FROM `{$tableName}` WHERE o_id = ? AND fieldname = ? ORDER BY `index` ASC",
                [$object->getId(), $this->getName()]
            );

            foreach ($rows as $row) {
                $item = $this->createBlockItemFromRow($row, $object);
                if ($item) {
                    $container->addItem($item);
                }
            }

            // Load localized data if enabled
            if ($this->allowLocalizedFields && $this->hasLocalizedFields()) {
                $this->loadLocalizedData($container, $object);
            }
        } catch (\Exception $e) {
            Logger::error('ExtendedBlock: Error loading block data: '.$e->getMessage());
        }

        return $container;
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

        // Map row data to item fields based on block definition
        $blockDef = $this->blockDefinitions[$type] ?? null;
        if ($blockDef && isset($blockDef['fields'])) {
            foreach ($blockDef['fields'] as $fieldDef) {
                if ($fieldDef instanceof Data && !($fieldDef instanceof Localizedfields)) {
                    $fieldName = $fieldDef->getName();
                    if (isset($row[$fieldName])) {
                        $value = $fieldDef->getDataFromResource($row[$fieldName], $object);
                        $item->setFieldValue($fieldName, $value);
                    }
                }
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
            // Check if localized table exists
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$localizedTableName]
            );

            if (!$tableExists) {
                return;
            }

            // Load all localized data at once for efficiency
            $itemIds = array_map(fn ($item) => $item->getId(), $container->getItems());
            if (empty($itemIds)) {
                return;
            }

            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $localizedRows = $db->fetchAllAssociative(
                "SELECT * FROM `{$localizedTableName}` WHERE ooo_id IN ({$placeholders})",
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
        } catch (\Exception $e) {
            Logger::error('ExtendedBlock: Error loading localized data: '.$e->getMessage());
        }
    }

    /**
     * Checks if this block definition contains localized fields.
     *
     * @return bool True if localized fields are present
     */
    public function hasLocalizedFields(): bool
    {
        foreach ($this->blockDefinitions as $blockDef) {
            if (isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $field) {
                    if ($field instanceof Localizedfields) {
                        return true;
                    }
                }
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
     * @param Concrete             $object The parent object being saved
     * @param array<string, mixed> $params Additional parameters
     */
    public function save(Concrete $object, array $params = []): void
    {
        $container = $object->getValueForFieldName($this->getName());
        if (!$container instanceof ExtendedBlockContainer) {
            return;
        }

        $db = Db::get();
        $tableName = $this->getTableName($object->getClassId());

        try {
            // Ensure table exists
            $this->ensureTableExists($object->getClassId());

            // Begin transaction for data integrity
            $db->beginTransaction();

            // Delete existing items for this object/field
            $db->executeStatement(
                "DELETE FROM `{$tableName}` WHERE o_id = ? AND fieldname = ?",
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
        } catch (\Exception $e) {
            $db->rollBack();
            Logger::error('ExtendedBlock: Error saving block data: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Saves a single block item to the database.
     *
     * @param ExtendedBlockItem         $item      The item to save
     * @param Concrete                  $object    The parent object
     * @param int                       $index     The item index/position
     * @param \Doctrine\DBAL\Connection $db        The database connection
     * @param string                    $tableName The target table name
     */
    protected function saveBlockItem(
        ExtendedBlockItem $item,
        Concrete $object,
        int $index,
        \Doctrine\DBAL\Connection $db,
        string $tableName,
    ): void {
        $data = [
            'o_id' => $object->getId(),
            'fieldname' => $this->getName(),
            'index' => $index,
            'type' => $item->getType(),
        ];

        // Add field values based on block definition
        $blockDef = $this->blockDefinitions[$item->getType()] ?? null;
        if ($blockDef && isset($blockDef['fields'])) {
            foreach ($blockDef['fields'] as $fieldDef) {
                if ($fieldDef instanceof Data && !($fieldDef instanceof Localizedfields)) {
                    $fieldName = $fieldDef->getName();
                    $value = $item->getFieldValue($fieldName);
                    $data[$fieldName] = $fieldDef->getDataForResource($value, $object);
                }
            }
        }

        $db->insert($tableName, $data);
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

        // Ensure localized table exists
        $this->ensureLocalizedTableExists($object->getClassId());

        // Delete existing localized data
        $itemIds = array_map(fn ($item) => $item->getId(), $container->getItems());
        if (!empty($itemIds)) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $db->executeStatement(
                "DELETE FROM `{$localizedTableName}` WHERE ooo_id IN ({$placeholders})",
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

                $data = [
                    'ooo_id' => $item->getId(),
                    'language' => $language,
                ];

                // Add localized field values
                $blockDef = $this->blockDefinitions[$item->getType()] ?? null;
                if ($blockDef && isset($blockDef['fields'])) {
                    foreach ($blockDef['fields'] as $fieldDef) {
                        if ($fieldDef instanceof Localizedfields) {
                            foreach ($fieldDef->getFieldDefinitions() as $localizedFieldDef) {
                                $fieldName = $localizedFieldDef->getName();
                                $value = $localizedData[$language][$fieldName] ?? null;
                                if (null !== $value) {
                                    $data[$fieldName] = $localizedFieldDef->getDataForResource($value, $object);
                                }
                            }
                        }
                    }
                }

                $db->insert($localizedTableName, $data);
            }
        }
    }

    /**
     * Deletes all block data for an object.
     *
     * Called when the parent object is deleted.
     *
     * @param Concrete             $object The object being deleted
     * @param array<string, mixed> $params Additional parameters
     */
    public function delete(Concrete $object, array $params = []): void
    {
        $db = Db::get();
        $tableName = $this->getTableName($object->getClassId());

        try {
            // Check if table exists before deleting
            $tableExists = $db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                [$tableName]
            );

            if (!$tableExists) {
                return;
            }

            // Get item IDs for localized data deletion
            $itemIds = $db->fetchFirstColumn(
                "SELECT id FROM `{$tableName}` WHERE o_id = ? AND fieldname = ?",
                [$object->getId(), $this->getName()]
            );

            // Delete localized data first
            if (!empty($itemIds) && $this->hasLocalizedFields()) {
                $localizedTableName = $this->getLocalizedTableName($object->getClassId());
                $localizedTableExists = $db->fetchOne(
                    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
                    [$localizedTableName]
                );

                if ($localizedTableExists) {
                    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                    $db->executeStatement(
                        "DELETE FROM `{$localizedTableName}` WHERE ooo_id IN ({$placeholders})",
                        $itemIds
                    );
                }
            }

            // Delete main items
            $db->executeStatement(
                "DELETE FROM `{$tableName}` WHERE o_id = ? AND fieldname = ?",
                [$object->getId(), $this->getName()]
            );
        } catch (\Exception $e) {
            Logger::error('ExtendedBlock: Error deleting block data: '.$e->getMessage());
        }
    }

    /**
     * Returns the main table name for this extended block.
     *
     * @param string $classId The class ID
     *
     * @return string The table name
     */
    public function getTableName(string $classId): string
    {
        return $this->tablePrefix.$classId.'_'.$this->getName();
    }

    /**
     * Returns the localized table name for this extended block.
     *
     * @param string $classId The class ID
     *
     * @return string The localized table name
     */
    public function getLocalizedTableName(string $classId): string
    {
        return $this->getTableName($classId).'_localized';
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
        $tableName = $this->getTableName($classId);
        $db = Db::get();

        // Check if table already exists
        $tableExists = $db->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        if ($tableExists) {
            return;
        }

        // Build CREATE TABLE statement
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`o_id` INT(11) UNSIGNED NOT NULL',
            '`fieldname` VARCHAR(70) NOT NULL',
            '`index` INT(11) UNSIGNED NOT NULL DEFAULT 0',
            '`type` VARCHAR(100) NOT NULL DEFAULT "default"',
        ];

        // Add columns for each field in block definitions
        foreach ($this->blockDefinitions as $blockDef) {
            if (isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $fieldDef) {
                    if ($fieldDef instanceof Data && !($fieldDef instanceof Localizedfields)) {
                        $columnType = $fieldDef->getColumnType();
                        if ($columnType) {
                            $columns[] = "`{$fieldDef->getName()}` {$columnType}";
                        }
                    }
                }
            }
        }

        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `o_id` (`o_id`)';
        $columns[] = 'INDEX `fieldname` (`fieldname`)';
        $columns[] = 'INDEX `type` (`type`)';

        $sql = "CREATE TABLE `{$tableName}` (\n".implode(",\n", $columns)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->executeStatement($sql);
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
        $tableName = $this->getLocalizedTableName($classId);
        $db = Db::get();

        // Check if table already exists
        $tableExists = $db->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );

        if ($tableExists) {
            return;
        }

        // Build CREATE TABLE statement
        $columns = [
            '`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT',
            '`ooo_id` INT(11) UNSIGNED NOT NULL',
            '`language` VARCHAR(10) NOT NULL',
        ];

        // Add columns for localized fields
        foreach ($this->blockDefinitions as $blockDef) {
            if (isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $fieldDef) {
                    if ($fieldDef instanceof Localizedfields) {
                        foreach ($fieldDef->getFieldDefinitions() as $localizedFieldDef) {
                            $columnType = $localizedFieldDef->getColumnType();
                            if ($columnType) {
                                $columns[] = "`{$localizedFieldDef->getName()}` {$columnType}";
                            }
                        }
                    }
                }
            }
        }

        $columns[] = 'PRIMARY KEY (`id`)';
        $columns[] = 'INDEX `ooo_id` (`ooo_id`)';
        $columns[] = 'INDEX `language` (`language`)';
        $columns[] = 'UNIQUE KEY `ooo_id_language` (`ooo_id`, `language`)';

        $sql = "CREATE TABLE `{$tableName}` (\n".implode(",\n", $columns)."\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $db->executeStatement($sql);
    }

    /**
     * Validates the field configuration.
     *
     * Checks for:
     * - Valid block definitions
     * - No nested ExtendedBlock in LocalizedFields
     * - No Block inside ExtendedBlock
     * - No ExtendedBlock inside Block
     * - Proper field naming
     *
     * @throws \Exception If validation fails
     */
    public function validate(): void
    {
        // Check for nested ExtendedBlock in LocalizedFields
        if ($this->disallowAddingInLocalizedField) {
            throw new \Exception('ExtendedBlock with localized fields cannot be added inside a LocalizedFields container. This would create an infinite recursion. Please restructure your class definition.');
        }

        // Validate block definitions
        foreach ($this->blockDefinitions as $typeName => $blockDef) {
            if (empty($typeName)) {
                throw new \Exception('Block type name cannot be empty');
            }

            if (isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $field) {
                    // Check for nested ExtendedBlock
                    if ($field instanceof self) {
                        throw new \Exception("ExtendedBlock cannot contain another ExtendedBlock. Type: {$typeName}");
                    }

                    // Check for Block inside ExtendedBlock
                    if ($field instanceof Block) {
                        throw new \Exception("ExtendedBlock cannot contain a Block. Type: {$typeName}. ".'Block nesting is not supported to ensure data integrity and prevent performance issues.');
                    }

                    // Check for ExtendedBlock/Block inside LocalizedFields
                    if ($field instanceof Localizedfields) {
                        foreach ($field->getFieldDefinitions() as $localizedField) {
                            if ($localizedField instanceof self) {
                                throw new \Exception('ExtendedBlock cannot be placed inside LocalizedFields within an ExtendedBlock. '."Type: {$typeName}");
                            }
                            if ($localizedField instanceof Block) {
                                throw new \Exception('Block cannot be placed inside LocalizedFields within an ExtendedBlock. '."Type: {$typeName}. Block nesting is not supported.");
                            }
                        }
                    }
                }
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
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return string The grid display value
     */
    public function getDataForGrid(mixed $data, ?Concrete $object = null, array $params = []): string
    {
        if ($data instanceof ExtendedBlockContainer) {
            $count = count($data->getItems());

            return sprintf('%d item%s', $count, 1 !== $count ? 's' : '');
        }

        return '0 items';
    }

    /**
     * Returns the data for editmode in admin.
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return array<string, mixed>|null The editmode data
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

            // Get field data
            $blockDef = $this->blockDefinitions[$item->getType()] ?? null;
            if ($blockDef && isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $fieldDef) {
                    if ($fieldDef instanceof Data && !($fieldDef instanceof Localizedfields)) {
                        $fieldName = $fieldDef->getName();
                        $value = $item->getFieldValue($fieldName);
                        $itemData['data'][$fieldName] = $fieldDef->getDataForEditmode($value, $object);
                    } elseif ($fieldDef instanceof Localizedfields) {
                        // Handle localized data
                        $localizedData = $item->getLocalizedData();
                        foreach ($localizedData as $language => $langData) {
                            $itemData['localizedData'][$language] = [];
                            foreach ($fieldDef->getFieldDefinitions() as $localizedFieldDef) {
                                $fieldName = $localizedFieldDef->getName();
                                $value = $langData[$fieldName] ?? null;
                                $itemData['localizedData'][$language][$fieldName] = $localizedFieldDef->getDataForEditmode($value, $object);
                            }
                        }
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

            // Process field data
            $blockDef = $this->blockDefinitions[$type] ?? null;
            if ($blockDef && isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $fieldDef) {
                    if ($fieldDef instanceof Data && !($fieldDef instanceof Localizedfields)) {
                        $fieldName = $fieldDef->getName();
                        if (isset($itemData['data'][$fieldName])) {
                            $value = $fieldDef->getDataFromEditmode($itemData['data'][$fieldName], $object);
                            $item->setFieldValue($fieldName, $value);
                        }
                    } elseif ($fieldDef instanceof Localizedfields && isset($itemData['localizedData'])) {
                        // Handle localized data
                        $localizedData = [];
                        foreach ($itemData['localizedData'] as $language => $langData) {
                            $localizedData[$language] = [];
                            foreach ($fieldDef->getFieldDefinitions() as $localizedFieldDef) {
                                $fieldName = $localizedFieldDef->getName();
                                if (isset($langData[$fieldName])) {
                                    $localizedData[$language][$fieldName] = $localizedFieldDef->getDataFromEditmode($langData[$fieldName], $object);
                                }
                            }
                        }
                        $item->setLocalizedData($localizedData);
                    }
                }
            }

            $container->addItem($item);
        }

        return $container;
    }

    /**
     * Returns data for JSON export.
     *
     * @param mixed                $data   The block data
     * @param Concrete|null        $object The parent object
     * @param array<string, mixed> $params Additional parameters
     *
     * @return array<string, mixed>|null The JSON-serializable data
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

            // Compare field values
            $blockDef = $this->blockDefinitions[$oldItem->getType()] ?? null;
            if ($blockDef && isset($blockDef['fields'])) {
                foreach ($blockDef['fields'] as $fieldDef) {
                    if ($fieldDef instanceof Data) {
                        $fieldName = $fieldDef->getName();
                        if (!$fieldDef->isEqual(
                            $oldItem->getFieldValue($fieldName),
                            $newItem->getFieldValue($fieldName)
                        )) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Returns the parameter data for the object getter.
     *
     * @return mixed The getter parameter data
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?\\'.ExtendedBlockContainer::class;
    }

    /**
     * Returns the return type declaration for getter.
     *
     * @return string|null The return type
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?\\'.ExtendedBlockContainer::class;
    }

    /**
     * Returns the PHP type declaration.
     *
     * @return string|null The type declaration
     */
    public function getPhpType(): ?string
    {
        return '\\'.ExtendedBlockContainer::class.'|null';
    }

    /**
     * Exports data for VarExporter.
     *
     * @param mixed                $value  The value to export
     * @param Concrete             $object The object
     * @param array<string, mixed> $params Parameters
     *
     * @return array<string, mixed>|null The exported data
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
     */
    public function getBlockDefinitions(): array
    {
        return $this->blockDefinitions;
    }

    /**
     * @param array<string, array<string, mixed>> $blockDefinitions
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

    public function setLazyLoading(bool $lazyLoading): static
    {
        $this->lazyLoading = $lazyLoading;

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
}
