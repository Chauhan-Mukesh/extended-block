<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Class Definition Event Listener
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\EventListener;

use Pimcore\Event\Model\DataObject\ClassDefinitionEvent;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data\ExtendedBlock;
use ExtendedBlockBundle\Service\TableSchemaService;
use Pimcore\Logger;

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
     *
     * @var TableSchemaService
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
     * @return void
     *
     * @throws \Exception If validation fails
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
     *
     * @return void
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
            } catch (\Exception $e) {
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
     *
     * @return void
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
                // Check inside localized fields
                foreach ($fieldDefinition->getFieldDefinitions() as $localizedFieldDef) {
                    if ($localizedFieldDef instanceof ExtendedBlock) {
                        $fields[] = [
                            'field' => $localizedFieldDef,
                            'context' => 'localizedfields',
                        ];
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Validates an ExtendedBlock field configuration.
     *
     * @param ExtendedBlock $field   The field to validate
     * @param string        $context The context ('root' or 'localizedfields')
     *
     * @return void
     *
     * @throws \Exception If validation fails
     */
    protected function validateExtendedBlockField(ExtendedBlock $field, string $context): void
    {
        // Check if ExtendedBlock with LocalizedFields is placed inside LocalizedFields
        if ($context === 'localizedfields' && $field->hasLocalizedFields()) {
            throw new \Exception(sprintf(
                'ExtendedBlock field "%s" contains LocalizedFields and cannot be placed ' .
                'inside a LocalizedFields container. This would create infinite recursion. ' .
                'Either remove the LocalizedFields from the ExtendedBlock definition, ' .
                'or move the ExtendedBlock outside of LocalizedFields.',
                $field->getName()
            ));
        }

        // Validate block definitions
        $field->validate();
    }
}
