<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Class Definition Event Listener.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\EventListener;

use Exception;
use ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data\ExtendedBlock;
use ExtendedBlockBundle\Service\TableSchemaService;
use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Block;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;

/**
 * Listener for class definition events.
 *
 * This listener handles:
 * - Creating/updating extended block tables when class definitions are saved
 * - Validating field configurations to prevent invalid nesting
 * - Cleaning up tables when extended block fields are removed
 *
 * @see https://pimcore.com/docs/platform/Events/events_list.html
 */
class ClassDefinitionListener
{
    /**
     * Service for managing table schemas.
     */
    protected TableSchemaService $tableSchemaService;

    /**
     * Creates a new ClassDefinitionListener.
     *
     * @param TableSchemaService $tableSchemaService The table schema service
     */
    public function __construct(TableSchemaService $tableSchemaService)
    {
        $this->tableSchemaService = $tableSchemaService;
    }

    /**
     * Called before a class definition is saved.
     *
     * Validates the configuration of extended block fields to prevent:
     * - Nested ExtendedBlock fields
     * - ExtendedBlock inside LocalizedFields when it contains LocalizedFields
     *
     * @param ClassDefinitionEvent $event The event
     *
     * @throws Exception If validation fails
     */
    public function onPreSave(ClassDefinitionEvent $event): void
    {
        $classDefinition = $event->getClassDefinition();

        // Find all extended block fields
        $extendedBlockFields = $this->findExtendedBlockFields($classDefinition);

        // Validate each field
        foreach ($extendedBlockFields as $fieldInfo) {
            $this->validateExtendedBlockField($fieldInfo['field'], $fieldInfo['context']);
        }

        Logger::debug('ExtendedBlock: Pre-save validation completed for class ' . $classDefinition->getName());
    }

    /**
     * Called after a class definition is saved.
     *
     * Creates or updates database tables for extended block fields.
     *
     * @param ClassDefinitionEvent $event The event
     */
    public function onPostSave(ClassDefinitionEvent $event): void
    {
        $classDefinition = $event->getClassDefinition();

        // Find all extended block fields
        $extendedBlockFields = $this->findExtendedBlockFields($classDefinition);

        // Create/update tables for each field
        foreach ($extendedBlockFields as $fieldInfo) {
            try {
                $this->tableSchemaService->createOrUpdateTable(
                    $classDefinition,
                    $fieldInfo['field']
                );

                Logger::info(sprintf(
                    'ExtendedBlock: Updated table for field %s in class %s',
                    $fieldInfo['field']->getName(),
                    $classDefinition->getName()
                ));
            } catch (Exception $e) {
                Logger::error(sprintf(
                    'ExtendedBlock: Failed to update table for field %s: %s',
                    $fieldInfo['field']->getName(),
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Called before a class definition is deleted.
     *
     * Note: We don't automatically drop tables to prevent data loss.
     * Tables should be dropped manually using the admin interface.
     *
     * @param ClassDefinitionEvent $event The event
     */
    public function onPreDelete(ClassDefinitionEvent $event): void
    {
        $classDefinition = $event->getClassDefinition();

        // Find all extended block fields
        $extendedBlockFields = $this->findExtendedBlockFields($classDefinition);

        if (!empty($extendedBlockFields)) {
            Logger::warning(sprintf(
                'ExtendedBlock: Class %s is being deleted but has %d extended block field(s). ' .
                'Database tables will be preserved. Use admin tools to remove them manually.',
                $classDefinition->getName(),
                count($extendedBlockFields)
            ));
        }
    }

    /**
     * Finds all ExtendedBlock fields in a class definition.
     *
     * ExtendedBlock can only be added at the root level of a class definition.
     * It is NOT allowed inside: LocalizedFields, FieldCollections, ObjectBricks, or Block.
     *
     * @param ClassDefinition $classDefinition The class definition
     *
     * @return array<array{field: ExtendedBlock, context: string}> Found fields with context
     */
    protected function findExtendedBlockFields(ClassDefinition $classDefinition): array
    {
        $fields = [];

        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof ExtendedBlock) {
                $fields[] = [
                    'field' => $fieldDefinition,
                    'context' => 'root',
                ];
            } elseif ($fieldDefinition instanceof Localizedfields) {
                // Check inside localized fields - ExtendedBlock inside LocalizedFields is not allowed
                $this->checkForExtendedBlockInLocalizedFields($fieldDefinition);
            } elseif ($fieldDefinition instanceof Block) {
                // Check inside Block fields - ExtendedBlock inside Block is not allowed
                $this->checkForExtendedBlockInBlock($fieldDefinition);
            } elseif ($fieldDefinition instanceof Fieldcollections) {
                // ExtendedBlock inside FieldCollections is not allowed
                $this->checkForExtendedBlockInFieldcollections($fieldDefinition);
            } elseif ($fieldDefinition instanceof Objectbricks) {
                // ExtendedBlock inside ObjectBricks is not allowed
                $this->checkForExtendedBlockInObjectbricks($fieldDefinition);
            }
        }

        return $fields;
    }

    /**
     * Checks if LocalizedFields contains ExtendedBlock, which is not allowed.
     *
     * ExtendedBlock can only be used at the root level of a class definition.
     *
     * @param Localizedfields $localizedFields The LocalizedFields to check
     *
     * @throws Exception If ExtendedBlock is found inside LocalizedFields
     */
    protected function checkForExtendedBlockInLocalizedFields(Localizedfields $localizedFields): void
    {
        foreach ($localizedFields->getFieldDefinitions() as $localizedFieldDef) {
            if ($localizedFieldDef instanceof ExtendedBlock) {
                throw new Exception(sprintf('ExtendedBlock field "%s" cannot be placed inside LocalizedFields. ExtendedBlock can only be used at the root level of a class definition.', $localizedFieldDef->getName()));
            }
        }
    }

    /**
     * Checks if FieldCollections contains ExtendedBlock, which is not allowed.
     *
     * ExtendedBlock can only be used at the root level of a class definition.
     *
     * @param Fieldcollections $fieldcollections The FieldCollections field to check
     *
     * @throws Exception If ExtendedBlock is found (validation happens at definition level)
     */
    protected function checkForExtendedBlockInFieldcollections(Fieldcollections $fieldcollections): void
    {
        // FieldCollections contain references to fieldcollection definitions
        // The actual field definitions are in the Fieldcollection\Definition classes
        // For now, we log a warning - the Fieldcollection definitions should be validated separately
        Logger::debug(sprintf('ExtendedBlock: FieldCollections field "%s" detected. Note: ExtendedBlock is not allowed inside FieldCollection definitions.', $fieldcollections->getName()));
    }

    /**
     * Checks if ObjectBricks contains ExtendedBlock, which is not allowed.
     *
     * ExtendedBlock can only be used at the root level of a class definition.
     *
     * @param Objectbricks $objectbricks The ObjectBricks field to check
     *
     * @throws Exception If ExtendedBlock is found (validation happens at definition level)
     */
    protected function checkForExtendedBlockInObjectbricks(Objectbricks $objectbricks): void
    {
        // ObjectBricks contain references to objectbrick definitions
        // The actual field definitions are in the Objectbrick\Definition classes
        // For now, we log a warning - the Objectbrick definitions should be validated separately
        Logger::debug(sprintf('ExtendedBlock: ObjectBricks field "%s" detected. Note: ExtendedBlock is not allowed inside ObjectBrick definitions.', $objectbricks->getName()));
    }

    /**
     * Checks if a Block field contains ExtendedBlock, which is not allowed.
     *
     * @param Block $blockField The Block field to check
     *
     * @throws Exception If ExtendedBlock is found inside Block
     */
    protected function checkForExtendedBlockInBlock(Block $blockField): void
    {
        $blockName = $blockField->getName();
        $fieldDefinitions = $blockField->getFieldDefinitions();

        foreach ($fieldDefinitions as $field) {
            if ($field instanceof ExtendedBlock) {
                throw new Exception(sprintf('ExtendedBlock field "%s" cannot be placed inside Block field "%s". Nesting ExtendedBlock inside Block is not supported.', $field->getName(), $blockName));
            }

            // Also check inside LocalizedFields within Block
            if ($field instanceof Localizedfields) {
                foreach ($field->getFieldDefinitions() as $localizedField) {
                    if ($localizedField instanceof ExtendedBlock) {
                        throw new Exception(sprintf('ExtendedBlock field "%s" cannot be placed inside LocalizedFields within Block field "%s". Nesting ExtendedBlock inside Block is not supported.', $localizedField->getName(), $blockName));
                    }
                }
            }
        }
    }

    /**
     * Validates an ExtendedBlock field configuration.
     *
     * @param ExtendedBlock $field   The field to validate
     * @param string        $context The context ('root' only - ExtendedBlock can only be at root level)
     *
     * @throws Exception If validation fails
     */
    protected function validateExtendedBlockField(ExtendedBlock $field, string $context): void
    {
        // Validate block definitions (checks for invalid nesting within the ExtendedBlock)
        $field->validate();
    }
}
