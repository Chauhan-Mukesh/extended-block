/**
 * Extended Block Bundle - Dynamic Type Module
 *
 * This module registers the ExtendedBlock data type with
 * Pimcore Studio UI's dynamic type registry.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

import { type AbstractModule, container } from '@pimcore/studio-ui-bundle'
import { serviceIds } from '@pimcore/studio-ui-bundle/app'
import {
  type DynamicTypeObjectDataRegistry,
  type DynamicTypeObjectDataAbstract
} from '@pimcore/studio-ui-bundle/modules/element'

/**
 * ExtendedBlock Module for Pimcore Studio UI.
 *
 * Registers the ExtendedBlock dynamic type with the object data registry
 * so it can be used in data object editing.
 */
export const ExtendedBlockModule: AbstractModule = {
  onInit (): void {
    // Get the object data registry from the DI container
    const objectDataRegistry = container.get<DynamicTypeObjectDataRegistry>(
      serviceIds['DynamicTypes/ObjectDataRegistry']
    )

    // Get and register the ExtendedBlock dynamic type
    const extendedBlockType = container.get<DynamicTypeObjectDataAbstract>(
      'DynamicTypes/ObjectData/ExtendedBlock'
    )
    objectDataRegistry.registerDynamicType(extendedBlockType)
  }
}
